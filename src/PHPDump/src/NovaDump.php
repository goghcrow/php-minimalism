<?php

namespace Minimalism\PHPDump;

use Minimalism\PHPDump\Nova\NovaLocalCodec;
use Minimalism\PHPDump\Nova\NovaCopy;
use Minimalism\PHPDump\Nova\NovaPacket;
use Minimalism\PHPDump\Nova\NovaPacketFilter;
use Minimalism\PHPDump\Nova\NovaProtocol;
use Minimalism\PHPDump\Pcap\Pcap;

$usage = <<<USAGE
Usage: 
    读取tcpdump pcap流或pcap文件, 实时解析nova请求, 支持tcpdump表达式过滤与nova服务过滤, 支持导出nova请求

    novadump --app=<应用名称> --path=<项目根路径> 
             -s=<服务fnmatch表达式> -m=<方法fnmatch表达式>
             --copy=<导出nova请求到nova-cli格式文件>
             --filter=<tcpdump过滤表达式> 
             --file=<pcap文件>   
    
    [建议] 填写--app= 与 --path= 否则不予解开thrift包, -s -m 过滤函数为fnmatch
     
    [example]:
    
    # 抓取本机所有nova包, 不解析请求参数与相应
    novadump
    
    # [推荐] 抓取本机pf-api应用的nova服务包 并解析请求参数与相应
    novadump --app=pf-api --path=/home/www/pf-api
    
    # 抓取 host 127.0.0.1 and port 8000 nova包, 不解析请求参数与相应
    novadump --filter='tcp and host 127.0.0.1 and port 8000'
    
    # 抓取 pf-api and host 127.0.0.1 and port 8000 nova包, 解析请求参数与相应
    novadump --app=pf-api --path=/home/www/pf-api --filter='tcp and host 127.0.0.1 and port 8000'
    
    # 抓取 pf-api and 127.0.0.1：8000 service 匹配 com.youzan.service.* 方法匹配 getBy* 的nova包
    novadump --app=pf-api --path=/home/www/pf-api --filter='tcp and host 127.0.0.1 and port 8000'
             -s=com.youzan.service.* -m=getBy*

    # 从tcpdump pcap文件读取nova包
    tcpdump -w /path/to/dump.pcap
    novadump --app=pf-api --path=/home/www/pf-api --file=/path/to/dump.pcap(phar包需要绝对路径)
    
    # 抓取 scrm-api nova包并导出
    novadump.php --app scrm-api --path /home/www/scrm-api/ --copy /path/to/nova.log

    [注意] 1. 暂不支持 PcapNG格式
          2. 仅仅适用于公司 centos 环境
             tcpdump version 4.1-PRE-CVS_2016_05_10
             libpcap version 1.4.0
             超过65535byte的nova包受限于tcpdump版本捕获字节数不足会导致程序退出
USAGE;

$self = __FILE__;
if (isset($argv[1]) && $argv[1] === "install") {
    `chmod +x $self && cp $self /usr/local/bin/novadump`;
    exit();
}

if (isset($argv[1]) && in_array($argv[1], ["help", "-h", "--help"], true)) {
    echo "\033[1m$usage\033[0m\n";
    exit(1);
}

if (trim(`whoami`) !== "root") {
    sys_abort("请使用root权限运行");
}

if (PHP_OS === "Darwin") {
    sys_abort("暂时只支持Linux");
}

$a = getopt("s:m:", ["app:", "path:", "filter:", "file:", "copy:"]);
if (isset($a["app"]) && isset($a["path"])) {
    $appName = $a["app"];
    $path = $a["path"];
    NovaLocalCodec::init($appName, $path);
}

$pcapFile = null;
$tcpFilter = "";
$servicePattern = null;
$methodPattern = null;
$exportFile = null;

if (isset($a["file"])) {
    if (!is_readable($a["file"]) || is_dir($a["file"])) {
        sys_abort("can not read file {$a["file"]}");
    }
    $pcapFile = $a["file"];
} else if (isset($a["filter"])) {
    $tcpFilter = $a["filter"];
} else {
    $tcpFilter = "tcp";
}

if (isset($a["s"])) {
    $servicePattern = $a["s"];
}

if (isset($a["m"])) {
    $methodPattern = $a["m"];
}

if (array_key_exists("copy", $a)) {
    $exportFile = $a["copy"];
}


Pcap::registerProtocol(new NovaProtocol());
$novaFilter = new NovaPacketFilter($servicePattern, $methodPattern);
NovaPacket::registerBefore($novaFilter);
if ($exportFile) {
    $novaCopy = new NovaCopy($exportFile);
    NovaPacket::registerAfter($novaCopy);
}


$phpDump = new PHPDump();
if ($pcapFile) {
    $phpDump->readFile($pcapFile);
} else {
    `ps aux|grep tcpdump | grep -v grep | awk '{print $2}' | sudo xargs kill -9 2> /dev/null`;
    $phpDump->readTcpdump($tcpFilter);
}