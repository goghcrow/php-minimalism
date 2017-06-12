<?php

namespace Minimalism\PHPDump;

use DirectoryIterator;
use Minimalism\PHPDump\Http\HttpCopy;
use Minimalism\PHPDump\Http\HttpPDU;
use Minimalism\PHPDump\Http\HttpDissector;
use Minimalism\PHPDump\MySQL\MySQLCopy;
use Minimalism\PHPDump\MySQL\MySQLDissector;
use Minimalism\PHPDump\MySQL\MySQLPDU;
use Minimalism\PHPDump\Nova\NovaLocalCodec;
use Minimalism\PHPDump\Nova\NovaCopy;
use Minimalism\PHPDump\Nova\NovaPDU;
use Minimalism\PHPDump\Nova\NovaPacketFilter;
use Minimalism\PHPDump\Nova\NovaDissector;
use Minimalism\PHPDump\Pcap\Pcap;
use Minimalism\PHPDump\Redis\RedisCopy;
use Minimalism\PHPDump\Redis\RedisDissector;
use Minimalism\PHPDump\Redis\RedisPDU;
use Phar;


define("USAGE", <<<'USAGE'
Usage: 
    读取tcpdump pcap流或pcap文件, 实时解析nova|http|mysql请求 
    支持tcpdump表达式过滤与nova服务过滤, 支持复制请求

    # 全部参数可选
    
    sudo php phpdump.phar install
    
    phpdump --protocol=nova|http|mysql
            --filter=tcpdump过滤表达式
            --file=pcap文件
            
            --app=应用名称,仅nova协议可用 
            --path=项目根路径,仅nova协议可用
            -s=服务fnmatch表达式,仅nova协议可用
            -m=方法fnmatch表达式,仅nova协议可用
            
            --copy=导出请求到文件,nova,http,mysql可用
            --debug=true 调试模式, 显示错误并退出
             
    
    [注意] 1. 暂不支持 PcapNG格式
          2. 仅仅适用于公司 centos 环境
             tcpdump version 4.1-PRE-CVS_2016_05_10
             libpcap version 1.4.0
             超过65535byte的nova包受限于tcpdump版本捕获字节数不足会导致程序退出
     
    [example]:
    
    # 交互式模式
    phpdump
    
    # HTTP
    phpdump --protocol=http
    phpdump --protocol=http --filter="tcp and port 80"
    
    # MYSQL 因协议识别问题，需要人工指定server端口
    phpdump --protocol=mysql
    phpdump --protocol=mysql --filter="tcp and port 3306"
    
    
    # NOVA
    # [建议] 填写--app= 与 --path= 否则不予解开thrift包, -s -m 过滤函数为fnmatch
    # 抓取本机所有nova包(可选选择应用), 不解析请求响应参数
    phpdump --protocol=nova
    
    # 抓取本机pf-api应用的nova服务包 并解析请求响应参数
    phpdump --protocol=nova --app=pf-api --path=/home/www/pf-api
    phpdump --protocol=nova --app=scrm-api --path=/home/www/scrm-api
    
    # 抓取 host 127.0.0.1 and port 8000 nova包, 不解析请求参数与相应
    phpdump --protocol=nova --filter='tcp and host 127.0.0.1 and port 8000'
    
    # 抓取 pf-api and host 127.0.0.1 and port 8000 nova包, 解析请求参数与相应
    phpdump -protocol=nova --app=pf-api --path=/home/www/pf-api --filter='tcp and host 127.0.0.1 and port 8000'
    phpdump --protocol=nova --app=scrm-api --path=/home/www/scrm-api --filter='tcp and host 127.0.0.1 and port 8000'
    
    # 抓取 pf-api and 127.0.0.1：8000 service 匹配 com.youzan.service.* 方法匹配 getBy* 的nova包
    phpdump --protocol=nova -app=pf-api --path=/home/www/pf-api --filter='tcp and host 127.0.0.1 and port 8000'
             -s=com.youzan.service.* -m=getBy*

    # 从tcpdump pcap文件读取nova包
    tcpdump -w /path/to/dump.pcap
    phpdump --protocol=nova --app=pf-api --path=/home/www/pf-api --file=/path/to/dump.pcap(phar包需要绝对路径)
    
    # 抓取 scrm-api nova包并导出
    phpdump.php --protocol=nova --app scrm-api --path /home/www/scrm-api/ --copy /path/to/nova.log

USAGE
);


function usage()
{
    $usage = USAGE;
    echo "\033[1m$usage\033[0m\n";
    exit;
}

function envCheck()
{
    global $argv;

    $phar = Phar::running(false);
    if (isset($argv[1]) && $argv[1] === "install") {
        `chmod +x $phar && cp $phar /usr/local/bin/phpdump`;
        exit();
    }

    if (isset($argv[1]) && in_array($argv[1], ["help", "-h", "--help"], true)) {
        usage();
    }

    if (trim(`whoami`) !== "root") {
        sys_abort("请使用root权限运行");
    }

    if (PHP_OS === "Darwin") {
        sys_abort("暂时只支持Linux");
    }
}


class Opt
{
    public $protocol;
    public $tcpFilter;
    public $pcapFile;
    public $exportFile;

    // nova
    public $app;
    public $path;
    public $servicePattern;
    public $methodPattern;

    public $debug;
}


function parseOpt()
{
    $opt = new Opt();
    $a = getopt("s:m:", ["app:", "path:", "filter:", "file:", "copy:", "protocol:", "debug:"]);

    if (isset($a["protocol"])) {
        $opt->protocol = $a["protocol"];
    }

    if (isset($a["file"])) {
        if (!is_readable($a["file"]) || is_dir($a["file"])) {
            sys_abort("can not read file {$a["file"]}");
        }
        $opt->pcapFile = $a["file"];
    } else if (isset($a["filter"])) {
        $opt->tcpFilter = $a["filter"];
    } else {
        $opt->tcpFilter = "tcp";
    }

    if (isset($a["s"])) {
        $opt->servicePattern = $a["s"];
    }

    if (isset($a["m"])) {
        $opt->methodPattern = $a["m"];
    }

    if (array_key_exists("copy", $a)) {
        $opt->exportFile = $a["copy"];
    }

    if (isset($a["app"]) && isset($a["path"])) {
        $opt->app = $a["app"];
        $opt->path = $a["path"];
    }

    if (isset($a["debug"])) {
        $opt->debug = true;
    }

    return $opt;
}


function nova_get_apps($path)
{
    $list = [];
    $prompt = "\n选择应用(可选):\n";
    $i = 1;

    if (file_exists($path) && is_dir($path)) {
        foreach (new DirectoryIterator($path) as $fileInfo) {
            if($fileInfo->isDot()) {
                continue;
            }
            $name = $fileInfo->getFilename();
            if ($name[0] === ".") {
                continue;
            }

            if ($fileInfo->isDir()) {
                $path = $fileInfo->getRealPath();

                $list[$i] = [$name, $path];
                $prompt .= "$i $name\n";
                $i++;
            }
        }
    }
    return [$list, $prompt];
}

function mysql_get_port_list()
{
    $runMode = getenv('KDT_RUN_MODE') ?: get_cfg_var('kdt.RUN_MODE') ?: "online";
    $postFix = "/resource/config/$runMode/connection/mysql.php";
    $postLen = strlen($postFix);

    $regex = '/.*\.php$/';
    $iter = new \RecursiveDirectoryIterator("/home/www", \RecursiveDirectoryIterator::SKIP_DOTS);
    $iter = new \RecursiveIteratorIterator($iter, \RecursiveIteratorIterator::LEAVES_ONLY);
    $iter = new \RegexIterator($iter, $regex, \RegexIterator::GET_MATCH);

    $namePort = [];

    foreach ($iter as $file) {
        $realPath = realpath($file[0]);
        if (substr($realPath, -$postLen) === $postFix) {
            /** @noinspection PhpIncludeInspection */
            $mysqlConf = @require $realPath;
            if (is_array($mysqlConf)) {
                foreach ($mysqlConf as $name => $conf) {
                    if (isset($conf["port"])) {
                        $namePort[$name] = $conf["port"];
                    }
                }
            }
        }
    }
    return $namePort;
}

function mysql_get_port(Opt $opt)
{
    $mysqlPort = null;
    $exps = array_map("strtolower", array_map("trim", explode(" ", $opt->tcpFilter)));

    foreach ($exps as $i => $exp) {
        if ($exp === "port") {
            if (isset($exps[$i + 1])) {
                $mysqlPort = $exps[$i + 1];
            }
            break;
        }
    }

    if ($mysqlPort === null) {
        $portList = mysql_get_port_list();

        echo "\n";
        foreach ($portList as $name => $port) {
            echo "$name $port\n";
        }
        $mysqlPort = read_line("输入MySQL Server监听端口号:\n");

        if ($mysqlPort) {
            $opt->tcpFilter .= " and port $mysqlPort";
        }
    }

    if (!$mysqlPort) {
        sys_abort("invalid mysql server port");
    }

    return $mysqlPort;
}




envCheck();


$opt = parseOpt();
if (!$opt->protocol) {
    $line = read_line(<<<'PROMPT'

选择协议:
1. NOVA
2. HTTP
3. MYSQL
4. REDIS

PROMPT
    );

    $protocols = [
        1 => "nova",
        2 => "http",
        3 => "mysql",
        4 => "redis",
    ];
    
    if (isset($protocols[intval($line)])) {
        $opt->protocol = $protocols[intval($line)];
    }
}

switch ($opt->protocol) {

    case "nova":
        if (!$opt->app || !$opt->path) {
            list($apps, $prompt) = nova_get_apps("/home/www");

            if ($apps) {
                $index = read_line($prompt);
                if ($index !== false && isset($apps[intval($index)])) {
                    list($opt->app, $opt->path) = $apps[intval($index)];
                }
            }
        }

        $decodeByThriftStub = $opt->app && $opt->path;

        if ($decodeByThriftStub) {
            NovaLocalCodec::init($opt->app, $opt->path);
        }

        if (!$opt->exportFile) {
            $opt->exportFile = "nova.log";
        }

        $novaDissector = new NovaDissector();
        Pcap::registerDissector($novaDissector);

        $novaFilter = new NovaPacketFilter($opt->servicePattern, $opt->methodPattern);
        NovaPDU::registerPreFilter($novaFilter);

        if ($decodeByThriftStub) {
            $novaCopy = new NovaCopy($opt->exportFile);
            NovaPDU::registerPostEvent($novaCopy);
        }

        break;

    case "http":
        if (!$opt->exportFile) {
            $opt->exportFile = "http.log";
        }

        $httpDissector = new HttpDissector();
        Pcap::registerDissector($httpDissector);

        $httpCopy = new HttpCopy($opt->exportFile);
        HttpPDU::registerPostEvent($httpCopy);

        break;

    case "mysql":
        $mysqlPort = mysql_get_port($opt);

        if (!$opt->exportFile) {
            $opt->exportFile = "mysql.log";
        }

        $mysqlDissector = new MySQLDissector($mysqlPort);
        Pcap::registerDissector($mysqlDissector);

        $mysqlCopy = new MySQLCopy($opt->exportFile);
        MySQLPDU::registerPostEvent($mysqlCopy);

        break;

    case "redis":

        if (!$opt->exportFile) {
            $opt->exportFile = "redis.log";
        }

        $redisDissector = new RedisDissector();
        Pcap::registerDissector($redisDissector);

        $redisCopy = new RedisCopy($opt->exportFile);
        RedisPDU::registerPostEvent($redisCopy);
        break;

    default:
        usage();
        exit(1);
}


set_error_handler(function($code, $message, $file, $line) use($opt) {
    global $argv;
    $self = strstr($argv[0], ".", true) ?: $argv[0];

    $t = date("H:i:s");
    $bt = backtrace();
    $msg = "$t::$file::$line::$code::$message\n$bt\n";

    if ($opt->debug) {
        sys_abort("$msg");
    } else {
        swoole_async_write("$self.err.log", "$msg\n\n", -1);
    }
});


$phpDump = new PHPDump();
if ($opt->pcapFile) {
    $phpDump->readFile($opt->pcapFile);
} else {
    `ps aux|grep tcpdump | grep -v grep | awk '{print $2}' | sudo xargs kill -9 2> /dev/null`;
    sys_echo("expression $opt->tcpFilter");
    $phpDump->readTcpdump($opt->tcpFilter);
}