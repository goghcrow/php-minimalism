#!/usr/bin/env php
<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/3/7
 * Time: 下午11:42
 */

use Terminal as T;

// 实时解码tcpdump -w pcap流: pcap -> linux sll -> ip -> tcp -> nova -> thrfit

$self = __FILE__;
if (isset($argv[1]) && $argv[1] === "install") {
    `chmod +x $self && cp $self /usr/local/bin/novadump`;
    exit();
}

if (trim(`whoami`) !== "root") {
    sys_abort("请使用root权限运行");
}
if (PHP_OS === "Darwin") {
    sys_abort("暂时只支持Linux");
}

$usage = <<<USAGE
Usage: 
    novadump --app=<应用名称> --path=<项目根路径> --filter=<tcpdump过滤表达式>
    
    novadump --app=pf-api --path=/home/www/pf-api
    novadump --filter='tcp and host 127.0.0.1 and port 8000'
    novadump --app=pf-api --path=/home/www/pf-api --filter='tcp and host 127.0.0.1 and port 8000'
USAGE;


$a = getopt("", ["app:", "path:", "filter:"]);
$ok = false;
if (isset($a["app"]) && isset($a["path"])) {
    $appName = $a["app"];
    $path = $a["path"];
    NovaApp::init($appName, $path);
    $ok = true;
}

if (isset($a["filter"])) {
    // $filter = preg_split('#\s+#', $a["filter"]);
    $filter = $a["filter"];
    $ok = true;
} else {
    $filter = "";
}

if (!$ok) {
    echo "\033[1m$usage\033[0m\n";
    exit(1);
}

`ps aux|grep tcpdump | grep -v grep | awk '{print $2}' | sudo xargs kill -9 2> /dev/null`;
$novadump = new NovaDump("handleNovaPacket");
$fd = $novadump->tcpdump($filter);
$novadump->readLoop($fd);




function handleNovaPacket($service, $method, $ip, $port, $seq, $attach, $thriftBin,
                          $rec_hdr, $linux_sll, $ip_hdr, $tcp_hdr)
{
    $isHeartbeat = $service === "com.youzan.service.test" && ($method === "ping" || $method === "pong");

    if ($isHeartbeat && NovaDump::$filterHeartbeat) {
        return;
    }

    $srcIp = $ip_hdr["source_ip"];
    $dstIp = $ip_hdr["destination_ip"];
    $srcPort = $tcp_hdr["source_port"];
    $dstPort = $tcp_hdr["destination_port"];

    $_ip = T::format($ip, T::BRIGHT);
    $_port = T::format($port, T::BRIGHT);
    $_seq = T::format($seq, T::BRIGHT);
    $_service = T::format($service, T::FG_GREEN);
    $_method = T::format($method, T::FG_GREEN);
    $_attach = T::format($attach, T::DIM);

    sys_echo("$srcIp:$srcPort > $dstIp:$dstPort, nova_ip $_ip, nova_port $_port, nova_seq $_seq");
    sys_echo("service $_service, method $_method");
    if ($attach) {
        sys_echo("attach $_attach");
    }

    if (!$isHeartbeat) {
        if (NovaApp::$enable) {
            try {
                $args = NovaApp::decodeServiceArgs($service, $method, $thriftBin);
                sys_echo("args " . T::format(json_encode($args), T::DIM));
            } catch (\Exception $ex) {
                echo $ex, "\n";
                sys_abort("nova_decode args fail");
            }
        } else {
            Hex::dump($thriftBin, "vvv/8/6");
        }
    }
    echo "\n";
}



// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=


class NovaDump
{
    public $buffer;
    public $pcap;
    /** @var \swoole_process  */
    public $proc;
    public $pid;

    public static $filterHeartbeat = false;

    public function __construct(callable $novaPacketHandler)
    {
        $this->buffer = new MemoryBuffer();
        $this->pcap = new Pcap($this->buffer, $novaPacketHandler);
    }

    public function tcpdump($filter = "")
    {
        $proc = new \swoole_process(function(\swoole_process $proc) use($filter) {
            $args = ["-i", "any", "-s", "0","-U", "-w", "-"];
            if ($filter) {
                $args[] = $filter;
            }
            $proc->exec("/usr/sbin/tcpdump", $args);
            $proc->exit();
        }, true, 1);

        $this->pid = $proc->start();
        if ($this->pid === false) {
            sys_abort("fork fail");
        }
        $this->proc = $proc;
        return $proc->pipe;
    }

    /**
     * sudo tcpdump -i any -s 0 -U -w - host 10.9.97.143 and port 8050 | hexdump
     * sudo tcpdump -i any -s0 -U -w - port 8050 | php novadump.php
     * @param int|resource $fd
     */
    public function readLoop($fd = STDIN)
    {
        swoole_event_add($fd, function($fd) {
            static $i = 0;

            if ($this->proc) {
                // hack~
                // !!!! buffer 太小会造成莫名其妙 pack包数据有问题....
                $recv = $this->proc->read(1024 * 1024 * 2);
                // swoole_process 将标准错误与标准输出重定向到一起了..这里先pass到两行tcpdump的标准错误输出
                if (++$i <= 2) {
                    sys_echo($recv);
                    $recv = "";
                }
            } else {
                $recv = stream_get_contents($fd);
            }
            // Hex::dump($recv, "vvv/8/6");
            $this->buffer->write($recv);
            $this->pcap->captureLoop();
        });

        register_shutdown_function(function() { `kill -9 {$this->pid}`; });
        // 控制 register_shutdown_function 执行顺序, 否则 kill 会比event_wait先执行
        swoole_event_wait();
    }
}


class NovaApp
{
    public static $enable = false;

    public $appName;
    public $appPath;

    public static function init($appName, $appPath)
    {
        new static($appName, $appPath);
    }

    public static function decodeServiceArgs($service, $method, $thriftBin)
    {
        $service = str_replace('.', '\\', ucwords($service, '.'));
        return \Kdt\Iron\Nova\Nova::decodeServiceArgs($service, $method, $thriftBin);
    }

    private function __construct($appName, $appPath)
    {
        $this->appName = $appName;
        $this->appPath = realpath($appPath);
        $this->scanSpec();
        static::$enable = true;
    }

    public function scanSpec()
    {
        $env = getenv('KDT_RUN_MODE') ?: get_cfg_var('kdt.RUN_MODE') ?: "online";
        $autoload = "$this->appPath/vendor/autoload.php";
        $novaPath = "$this->appPath/resource/config/$env/nova.php";
        if (!is_readable($autoload)) {
            sys_abort("$autoload is not readable");
        }
        if (!is_readable($novaPath)) {
            sys_abort("$novaPath is not readable");
        }
        require $autoload;
        $novaConf = require $novaPath;

        $path = new \ReflectionClass(\Zan\Framework\Foundation\Core\Path::class);
        $propPath = $path->getProperty("rootPath");
        $propPath->setAccessible(true);
        $propPath->setValue($this->appPath . "/");

        \Kdt\Iron\Nova\Nova::init($this->parserNovaConfig($novaConf["novaApi"]));
    }

    public function parserNovaConfig(array $novaApi)
    {
        $rootPath = "$this->appPath/";
        if (isset($novaApi["path"])) {
            if (!isset($novaApi["appName"])) {
                $novaApi["appName"] = $this->appName;
            }
            $novaApi = [ $novaApi ];
        }

        foreach ($novaApi as &$item) {
            if (!isset($item["appName"])) {
                $item["appName"] = $this->appName;
            }
            $item["path"] = $rootPath . $item["path"];
            if(!isset($item["domain"])) {
                $item["domain"] = "com.youzan.service";
            }
            if(!isset($item["protocol"])) {
                $item["protocol"] = "nova";
            }
        }
        unset($item);
        return $novaApi;
    }
}


/**
 * Class Pcap
 */
class Pcap
{
    const MAX_PACKET_SIZE = 1024 * 1024 * 2;

    /** @var string pacp order byte unsigned int format */
    public $u32;
    /** @var string pacp order byte unsigned short format */
    public $u16;
    /** @var string pacp order byte unsigned char format */
    public $uC;

    /** @var MemoryBuffer $buffer pcap buffer */
    public $buffer;

    public $pcap_hdr;

    public $novaPacketHandler;

    /**
     * @var MemoryBuffer[] $novaConnsBuffers
     *
     * srcIp:scrPort-dstIp:dstPort => MemoryBuffer
     * 每条虚拟连接一个Buffer, 用于处理粘包
     *
     */
    public $novaConnsBuffers = [];

    public function __construct(MemoryBuffer $buffer, callable $novaPacketHandler)
    {
        $this->buffer = $buffer;
        $this->novaPacketHandler = $novaPacketHandler;
    }

    /**
     * 解码 Pcap流hdr, 只处理一次
     *
     * typedef struct pcap_hdr_s {
     *      guint32 magic_number;   // magic number
     *      guint16 version_major;  // major version number
     *      guint16 version_minor;  // minor version number
     *      gint32  thiszone;       // GMT to local correction
     *      guint32 sigfigs;        // accuracy of timestamps
     *      guint32 snaplen;        // max length of captured packets, in octets
     *      guint32 network;        // data link type
     * } pcap_hdr_t;
     *
     */
    private function unpackGlobalHdr()
    {
        if ($this->pcap_hdr !== null) {
            return;
        }

        // 24 pcap hdr sige
        if ($this->buffer->readableBytes() < 24) {
            // sys_abort("pcap buffer size too small");
            return;
        }

        $u32 = is_big_endian() ? "V" : "N";
        $magic_number = unpack("{$u32}magic_number", $this->buffer->read(4))["magic_number"];
        $magic_number = sprintf("%x",$magic_number);

        // 根据magic判断字节序
        // be
        if ($magic_number === "a1b2c3d4") {
            $this->u32 = "V";
            $this->u16 = "v";
            $this->uC = "C";
            // le
        } else if ($magic_number === "d4c3b2a1") {
            $this->u32 = "N";
            $this->u16 = "n";
            $this->uC = "C";
        } else {
            sys_abort("unsupport pcap magic");
        }

        /* Reading global header */
        $hdr_u16 = "v";
        $hdr_u32 = "V";
        $hdr = [
            $hdr_u16 . "version_major/",
            $hdr_u16 . "version_minor/",
            $hdr_u32 . "thiszone/",
            $hdr_u32 . "sigfigs/",
            $hdr_u32 . "snaplen/",
            $hdr_u32 . "network",
        ];
        $pcap_hdr = unpack(implode($hdr), $this->buffer->read(20));
        $pcap_hdr["magic_number"] = $magic_number;

        // 注意: tcpdump 产生的是一种Linux “cooked” capture 的 linktype
        // network --> linktype
        // http://www.tcpdump.org/linktypes.html
        // 113         special Linux “cooked” capture
        // http://www.tcpdump.org/linktypes/LINKTYPE_LINUX_SLL.html

        if ($pcap_hdr["network"] !== 113) {
            sys_abort("only support special Linux “cooked” capture");
        }

        if ($pcap_hdr["snaplen"] !== 65535) {
            sys_abort("please set snaplen=0, tcpdump -s 0");
        }

        $this->pcap_hdr = $pcap_hdr;
        // sys_echo("pcap_hdr " . json_encode($this->pcap_hdr, JSON_PRETTY_PRINT));
    }

    /**
     * Read packet header
     *
     * typedef struct pcaprec_hdr_s {
     *      guint32 ts_sec;         // timestamp seconds
     *      guint32 ts_usec;        // timestamp microseconds
     *      guint32 incl_len;       // number of octets of packet saved in file
     *      guint32 orig_len;       // actual length of packet
     * } pcaprec_hdr_t;
     * @param string $hdr
     * @return array
     */
    private function unpackPacketHdr($hdr)
    {
        $rec_hdr_u32 = "V";
        $rec_hdr_fmt = [
            $rec_hdr_u32 . "ts_sec/",
            $rec_hdr_u32 . "ts_usec/",
            $rec_hdr_u32 . "incl_len/",
            $rec_hdr_u32 . "orig_len/",
        ];
        $rec_hdr = unpack(implode($rec_hdr_fmt), $hdr);
        // sys_echo("pcap_rec_hdr " . json_encode($rec_hdr, JSON_PRETTY_PRINT));

        $snaplen = $this->pcap_hdr["snaplen"];
        $incl_len = $rec_hdr["incl_len"];
        $orig_len = $rec_hdr["orig_len"];

        if ($incl_len > static::MAX_PACKET_SIZE) {
            // Hex::dump($hdr, "vvv/8/6");
            // Hex::dump($this->buffer->readFull(), "vvv/8/6");
            sys_abort("too large incl_len, $incl_len > " . static::MAX_PACKET_SIZE);
        }
        if ($incl_len > $orig_len || $incl_len > $snaplen || $incl_len === 0) {
            // Hex::dump($hdr, "vvv/8/6");
            // Hex::dump($this->buffer->readFull(), "vvv/8/6");
            sys_abort("malformed read pcap record header");
        }

        return $rec_hdr;
    }

    private function unpackEtherHdr(MemoryBuffer $recordBuffer)
    {
        $ether = [];
        /*
            // http://www.ituring.com.cn/article/42619
            // 它总共占14个字节。分别是6个字节的目标MAC地址、6个字节的源MAC地址以及2个字节的上层协议类型。
            $ether['destination_mac'] = bin2hex($recordBuffer->read(6));
            $ether['source_mac'] = bin2hex($recordBuffer->read(6));

            $r = unpack($this->u16 . "ethertype", $recordBuffer->read(2));
            if(!isset($r["ethertype"])) {
                sys_abort("malformed ether header");
            }
            $ether["ethertype"] = $r["ethertype"];
        */
        return $ether;
    }

    /**
     *
     * Octet 8位元组 8个二进制位
     *
     * Byte 通常情也表示8个bit
     * Byte 表示CPU可以独立的寻址的最小内存单位（不过通过移位和逻辑运算，CPU也可以寻址到某一个单独的bit）
     *
     * @see http://www.tcpdump.org/linktypes/LINKTYPE_LINUX_SLL.html
     * +---------------------------+
     * |         Packet type       |
     * |         (2 Octets)        |
     * +---------------------------+
     * |        ARPHRD_ type       |
     * |         (2 Octets)        |
     * +---------------------------+
     * | Link-layer address length |
     * |         (2 Octets)        |
     * +---------------------------+
     * |    Link-layer address     |
     * |         (8 Octets)        |
     * +---------------------------+
     * |        Protocol type      |
     * |         (2 Octets)        |
     * +---------------------------+
     * |           Payload         |
     * .                           .
     * .                           .
     * .                           .
     * @param MemoryBuffer $recordBuffer
     * @return array
     */
    private function unpackLinuxSLL(MemoryBuffer $recordBuffer)
    {
        $linux_sll_fmt = [
            $this->u16 . "packet_type/",
            $this->u16 . "arphdr_type/",
            $this->u16 . "address_length/",
            $this->u32 . "address_1/",
            $this->u32 . "address_2/",
            $this->u16 . "type",
        ];

        // TODO 这里计算的address不对, 应该先获取length, 根据length获取指定长度length

//            $linux_sll = [];
//            $linux_sll["packet_type"] = unpack();
//            $linux_sll["arphdr_type"] = "";
//            $linux_sll["address_length"] = "";
//            $linux_sll["address"] = "";
//            $linux_sll["type"] = "";

        $linux_sll = unpack(implode($linux_sll_fmt), $recordBuffer->read(16));

        // BE
        if ($this->u32 === "N") {
            $hi = $linux_sll["address_1"];
            $low = $linux_sll["address_2"];
            $linux_sll["address"] = bcadd(bcmul($hi, "4294967296", 0), $low);
        }
        // LE
        else {
            $hi = $linux_sll["address_2"];
            $low = $linux_sll["address_1"];
            $linux_sll["address"] = bcadd(bcmul($hi, "4294967296", 0), $low);
        }

        return $linux_sll;
    }

    private function unpackIpHdr(MemoryBuffer $recordBuffer)
    {
        $ip_hdr = [
            $this->uC . "version_ihl/",
            $this->uC . "services/",
            $this->u16 . "length/",
            $this->u16 . "identification/",
            $this->u16 . "flags_offset/",
            $this->uC . "ttl/",
            $this->uC . "protocol/",
            $this->u16 . "checksum/",
            $this->u32 . "source/",
            $this->u32 . "destination",
        ];


        $ip = unpack(implode($ip_hdr), $recordBuffer->read(20));
        if( !isset($ip["version_ihl"]) )
            sys_abort("malformed ip header");

        $ip['version'] = $ip['version_ihl'] >> 4;
        $ip['ihl'] = $ip['version_ihl'] & 0xf;
        unset($ip['version_ihl']);
        $ip['flags'] = $ip['flags_offset'] >> 13;
        $ip['offset'] = $ip['flags_offset'] & 0x1fff;
        $ip['source_ip'] = long2ip($ip['source']);
        $ip['destination_ip'] = long2ip($ip['destination']);

        // TODO 计算ip options, 否则一旦ip有options, 后面解出来的都是错误
        // ignoring options

        return $ip;
    }

    /**
     *
     *    0                            15                              31
     *    -----------------------------------------------------------------
     *    |          source port          |       destination port        |
     *    -----------------------------------------------------------------
     *    |                        sequence number                        |
     *    -----------------------------------------------------------------
     *    |                     acknowledgment number                     |
     *    -----------------------------------------------------------------
     *    |  HL   | rsvd  |C|E|U|A|P|R|S|F|        window size            |
     *    -----------------------------------------------------------------
     *    |         TCP checksum          |       urgent pointer          |
     *    -----------------------------------------------------------------
     *
     * @param MemoryBuffer $recordBuffer
     * @return array
     */
    private function unpackTcpHdr(MemoryBuffer $recordBuffer)
    {
        // HL, HLEN, offset, 数据偏移量, 4位包括TCP头大小, 指示何处数据开始
        $tcp_hdr = [
            $this->u16 . "source_port/",
            $this->u16 . "destination_port/",
            $this->u32 . "seq/",
            $this->u32 . "ack/",
            $this->uC  . "tmp1/",
            $this->uC  . "tmp2/",
            $this->u16 . "window/",
            $this->u16 . "checksum/",
            $this->u16 . "urgent"
        ];
        $tcp = unpack(implode($tcp_hdr), $recordBuffer->read(20));
        if(!isset($tcp["tmp1"])) {
            sys_abort("malformed tcp header");
        }

        // CWR | ECE | URG | ACK | PSH | RST | SYN | FIN
        $tcp['offset']   = ($tcp['tmp1']>>4)&0xf;
        $tcp['flag_NS']  = ($tcp['tmp1']&0x01) != 0;

        $tcp['flag_CWR'] = ($tcp['tmp2']&0x80) != 0;
        $tcp['flag_ECE'] = ($tcp['tmp2']&0x40) != 0;
        $tcp['flag_URG'] = ($tcp['tmp2']&0x20) != 0;
        $tcp['flag_ACK'] = ($tcp['tmp2']&0x10) != 0;
        $tcp['flag_PSH'] = ($tcp['tmp2']&0x08) != 0;
        $tcp['flag_RST'] = ($tcp['tmp2']&0x04) != 0;
        $tcp['flag_SYN'] = ($tcp['tmp2']&0x02) != 0;
        $tcp['flag_FIN'] = ($tcp['tmp2']&0x01) != 0;
        unset($tcp['tmp1']);
        unset($tcp['tmp2']);

        // 计算options 长度
        $options_len = $tcp["offset"] * 4 - 20;
        $recordBuffer->read($options_len); // ignoring options

        return $tcp;
    }

    /**
     * #define NOVA_MAGIC              0xdabc
     * #define NOVA_HEADER_COMMON_LEN  37
     *
     * Header
     *
     * typedef struct swNova_Header{
     *      int32_t     msg_size; // contains body
     *      uint16_t    magic;
     *      int16_t     head_size;
     *      int8_t      version;
     *      uint32_t    ip;
     *      uint32_t    port;
     *      int32_t     service_len;
     *      char        *service_name;
     *      int32_t     method_len;
     *      char        *method_name;
     *      int64_t     seq_no; // req id <-> res id
     *      int32_t     attach_len;
     *      char        *attach;
     *  }swNova_Header;
     *
     * Body
     *
     * message body thrift serialize
     *
     * @param string $nova_data
     * @param array $rec_hdr
     * @param array $linux_sll
     * @param array $ip_hdr
     * @param array $tcp_hdr
     */
    private function unpackNova($nova_data, $rec_hdr, $linux_sll, $ip_hdr, $tcp_hdr)
    {
        // if ($msg_size < nova_header_size)
        // is_nova_packet($data)
        $ok = nova_decode($nova_data, $service, $method, $ip, $port, $seq, $attach, $thriftBin);
        if (!$ok) {
            sys_abort("nova_decode fail, hex: " . bin2hex($nova_data));
        }
        $ip = long2ip($ip);

        try {
            $fn = $this->novaPacketHandler;
            $fn($service, $method, $ip, $port, $seq, $attach, $thriftBin, $rec_hdr, $linux_sll, $ip_hdr, $tcp_hdr);
        } catch (\Exception $ex) {
            echo $ex, "\n";
            sys_abort("handle nova pack fail");
        }
    }

    private function recordReceived()
    {
        // 16 pcaprec_hdr_s size
        $readableBytes = $this->buffer->readableBytes();
        if ($readableBytes < 16) {
            return false;
        }
        $hdr = $this->buffer->get(16);
        $rec_hdr = $this->unpackPacketHdr($hdr);
        $incl_len = $rec_hdr["incl_len"];
        return $readableBytes >= ($incl_len + 16);
    }

    public function captureLoop()
    {
        $this->unpackGlobalHdr();

        /* Loop reading packets */
        while ($this->recordReceived()) {
            /* Read packet header */

            $hdr = $this->buffer->read(16);
            // Hex::dump($hdr, "vvv/8/6");
            $rec_hdr = $this->unpackPacketHdr($hdr);

            // -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

            /* Read packet raw data */
            $incl_len = $rec_hdr["incl_len"];
            $record = $this->buffer->read($incl_len);
            if ($incl_len !== strlen($record)) {
                sys_abort("unexpected record length");
            }
            $recordBuffer = MemoryBuffer::ofBytes($record);

            // -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
            // 注意: 此处不是ether
            // $ether = $this->unpackEtherHdr();
            // 0x0800 ipv4
            // if ($ether["ethertype"] !== 0x0800) {
            //     return false; // continue;
            // }
            // -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

            $linux_sll = $this->unpackLinuxSLL($recordBuffer);
            // sys_echo("linux_sll " . json_encode($linux_sll, JSON_PRETTY_PRINT));

            // -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

            $ip = $this->unpackIpHdr($recordBuffer);
            // sys_echo("ip_hdr " . json_encode($ip, JSON_PRETTY_PRINT));

            // 0x01 ICMP   0x06 TCP    0x11 UDP
            // 只处理 TCP 包
            if ($ip["protocol"] !== 0x06) {
                $this->buffer->reset();
                continue;
            }

            // -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

            $tcp = $this->unpackTcpHdr($recordBuffer);
            // sys_echo("tcp_hdr " . json_encode($tcp, JSON_PRETTY_PRINT));

            // -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

            $srcIp = $ip["source_ip"];
            $dstIp = $ip["destination_ip"];
            $srcPort = $tcp["source_port"];
            $dstPort = $tcp["destination_port"];

            $connKey = "$srcIp:$srcPort-$dstIp:$dstPort";
            if (!isset($this->novaConnsBuffers[$connKey])) {
                // fitler nova packet
                // #define NOVA_HEADER_COMMON_LEN 37
                /** @noinspection PhpUndefinedFunctionInspection */
                if ($recordBuffer->readableBytes() >= 37 && is_nova_packet($recordBuffer->get(37))) {
                    $this->novaConnsBuffers[$connKey] = new MemoryBuffer();
                } else {
                    continue;
                }
            }

            /** @var MemoryBuffer $connBuffer */
            $connBuffer = $this->novaConnsBuffers[$connKey];

            // move bytes
            $connBuffer->write($recordBuffer->readFull());

            // -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

            // 4byte nova msg_size
            if ($connBuffer->readableBytes() < 4) {
                continue;
            }

            $msg_size = unpack("Nmsg_size", $connBuffer->get(4))["msg_size"];
            if ($msg_size > static::MAX_PACKET_SIZE) {
                sys_abort("capture too large nova packet, $msg_size > 1024 * 1024 * 2");
            }
            if ($connBuffer->readableBytes() < $msg_size) {
                continue;
            }

            $nova_data = $connBuffer->read($msg_size);
            $this->unpackNova($nova_data, $rec_hdr, $linux_sll, $ip, $tcp);
        }
    }
}


/**
 * Class MemoryBuffer
 *
 * 基于 swoole_buffer read,write,expand 接口
 *
 * @author xiaofeng
 *
 * 自动扩容, 从尾部写入数据，从头部读出数据
 *
 * +-------------------+------------------+------------------+
 * | prependable bytes |  readable bytes  |  writable bytes  |
 * |                   |     (CONTENT)    |                  |
 * +-------------------+------------------+------------------+
 * |                   |                  |                  |
 * V                   V                  V                  V
 * 0      <=      readerIndex   <=   writerIndex    <=     size
 *
 */
class MemoryBuffer
{
    private $buffer;

    private $readerIndex;

    private $writerIndex;

    /**
     * @param $bytes
     * @return static
     */
    public static function ofBytes($bytes)
    {
        $self = new static;
        $self->write($bytes);
        return $self;
    }

    public function __construct($size = 1024)
    {
        $this->buffer = new \swoole_buffer($size);
        $this->readerIndex = 0;
        $this->writerIndex = 0;
    }

    public function readableBytes()
    {
        return $this->writerIndex - $this->readerIndex;
    }

    public function writableBytes()
    {
        return $this->buffer->capacity - $this->writerIndex;
    }

    public function prependableBytes()
    {
        return $this->readerIndex;
    }

    public function capacity()
    {
        return $this->buffer->capacity;
    }

    public function get($len)
    {
        if ($len <= 0) {
            return "";
        }

        $len = min($len, $this->readableBytes());
        return $this->rawRead($this->readerIndex, $len);
    }

    public function read($len)
    {
        if ($len <= 0) {
            return "";
        }

        $len = min($len, $this->readableBytes());
        $read = $this->rawRead($this->readerIndex, $len);
        $this->readerIndex += $len;
        if ($this->readerIndex === $this->writerIndex) {
            $this->reset();
        }
        return $read;
    }

    public function skip($len)
    {
        $len = min($len, $this->readableBytes());
        $this->readerIndex += $len;
        if ($this->readerIndex === $this->writerIndex) {
            $this->reset();
        }
        return $len;
    }

    public function readFull()
    {
        return $this->read($this->readableBytes());
    }

    public function write($bytes)
    {
        if ($bytes === "") {
            return false;
        }

        $len = strlen($bytes);

        if ($len <= $this->writableBytes()) {

            write:
            $this->rawWrite($this->writerIndex, $bytes);
            $this->writerIndex += $len;
            return true;
        }

        // expand
        if ($len > ($this->prependableBytes() + $this->writableBytes())) {
            $this->expand(($this->readableBytes() + $len) * 2);
        }

        // copy-move
        if ($this->readerIndex !== 0) {
            $this->rawWrite(0, $this->rawRead($this->readerIndex, $this->writerIndex - $this->readerIndex));
            $this->writerIndex -= $this->readerIndex;
            $this->readerIndex = 0;
        }

        goto write;
    }

    public function reset()
    {
        $this->readerIndex = 0;
        $this->writerIndex = 0;
    }

    public function __toString()
    {
        return $this->rawRead($this->readerIndex, $this->writerIndex - $this->readerIndex);
    }

    // NOTICE: 影响 IDE Debugger
    public function __debugInfo()
    {
        return [
            "string" => $this->__toString(),
            "capacity" => $this->capacity(),
            "readerIndex" => $this->readerIndex,
            "writerIndex" => $this->writerIndex,
            "prependableBytes" => $this->prependableBytes(),
            "readableBytes" => $this->readableBytes(),
            "writableBytes" => $this->writableBytes(),
        ];
    }

    private function rawRead($offset, $len)
    {
        if ($offset < 0 || $offset + $len > $this->buffer->capacity) {
            throw new \InvalidArgumentException(__METHOD__ . ": offset=$offset, len=$len, capacity={$this->buffer->capacity}");
        }
        return $this->buffer->read($offset, $len);
    }

    private function rawWrite($offset, $bytes)
    {
        $len = strlen($bytes);
        if ($offset < 0 || $offset + $len > $this->buffer->capacity) {
            throw new \InvalidArgumentException(__METHOD__ . ": offset=$offset, len=$len, capacity={$this->buffer->capacity}");
        }
        return $this->buffer->write($offset, $bytes);
    }

    private function expand($size)
    {
        if ($size <= $this->buffer->capacity) {
            throw new \InvalidArgumentException(__METHOD__ . ": size=$size, capacity={$this->buffer->capacity}");
        }
        return $this->buffer->expand($size);
    }
}

/**
 * Class Binary
 *
 * @author xiaofeng
 *
 */
class Binary
{
    private $buffer;

    public function __construct(MemoryBuffer $buffer = null)
    {
        if ($buffer === null) {
            $this->buffer = new MemoryBuffer();
        } else {
            $this->buffer = $buffer;
        }
    }

    public function writeUInt8($i)
    {
        return $this->buffer->write(pack('C', $i));
    }

    public function writeUInt16BE($i)
    {
        return $this->buffer->write(pack('n', $i));
    }

    public function writeUInt16LE($i)
    {
        return $this->buffer->write(pack('v', $i));
    }

    public function writeUInt32BE($i)
    {
        return $this->buffer->write(pack('N', $i));
    }

    public function writeUInt32LE($i)
    {
        return $this->buffer->write(pack('V', $i));
    }

    public function writeUInt64BE($uint64Str)
    {
        $low = bcmod($uint64Str, "4294967296");
        $hi = bcdiv($uint64Str, "4294967296", 0);
        return $this->buffer->write(pack("NN", $hi, $low));
    }

    public function writeUInt64LE($uint64Str)
    {
        $low = bcmod($uint64Str, "4294967296");
        $hi = bcdiv($uint64Str, "4294967296", 0);
        return $this->buffer->write(pack('VV', $low, $hi));
    }

    public function writeInt32BE($i)
    {
        return $this->buffer->write(pack('N', $i));
    }

    public function writeInt32LE($i)
    {
        return $this->buffer->write(pack('V', $i));
    }

    public function writeFloat($f)
    {
        return $this->buffer->write(pack('f', $f));
    }

    public function writeDouble($d)
    {
        return $this->buffer->write(pack('d', $d));
    }

    public function readUInt8()
    {
        $ret = unpack("Cr", $this->buffer->read(1));
        return $ret == false ? null : $ret["r"];
    }

    public function readUInt16BE()
    {
        $ret = unpack("nr", $this->buffer->read(2));
        return $ret === false ? null : $ret["r"];
    }

    public function readUInt16LE()
    {
        $ret = unpack("vr", $this->buffer->read(2));
        return $ret === false ? null : $ret["r"];
    }

    public function readUInt32BE()
    {
        $ret = unpack("nhi/nlo", $this->buffer->read(4));
        return $ret === false ? null : (($ret["hi"] << 16) | $ret["lo"]);
    }

    public function readUInt32LE()
    {
        $ret = unpack("vlo/vhi", $this->buffer->read(4));
        return $ret === false ? null : (($ret["hi"] << 16) | $ret["lo"]);
    }

    public function readUInt64BE()
    {
        $param = unpack("Nhi/Nlow", $this->buffer->read(8));
        return bcadd(bcmul($param["hi"], "4294967296", 0), $param["low"]);
    }

    public function readUInt64LE()
    {
        $param = unpack("Vlow/Vhi", $this->buffer->read(8));
        return bcadd(bcmul($param["hi"], "4294967296", 0), $param["low"]);
    }

    public function readInt32BE()
    {
        $ret = unpack("Nr", $this->buffer->read(4));
        return $ret === false ? null : $ret["r"];
    }

    public function readInt32LE()
    {
        $ret = unpack("Vr", $this->buffer->read(4));
        return $ret === false ? null : $ret["r"];
    }

    public function readFloat()
    {
        $ret = unpack("fr", $this->buffer->read(4));
        return $ret === false ? null : $ret["r"];
    }

    public function readDouble()
    {
        $ret = unpack("dr", $this->buffer->read(8));
        return $ret === false ? null : $ret["r"];
    }

    public function write($bytes)
    {
        return $this->buffer->write($bytes);
    }

    public function read($len)
    {
        return $this->buffer->read($len);
    }

    public function readFull()
    {
        return $this->buffer->readFull();
    }

    public function reset()
    {
        $this->buffer->reset();
    }
}

/**
 * Class Hex
 * @author xiaofeng
 */
class Hex
{
    /**
     * @param string $str
     * @param string $fmt
     *
     * fmt 由3部分构成, 中间用  / 分隔, 可省略
     * fmt构成: dump类型v|vv|vvv / nColumns / nHexs
     *
     * e.g. dump($str, "vvv/8/4") // tcpdump 格式打印
     */
    public static function dump($str, $fmt = "")
    {
        // 填充默认值 8 4
        list($v, $nGroups, $perGroup, ) = explode("/", "$fmt/8/4");
        $sep = " ";

        switch ($v) {
            case "v":
                fwrite(STDERR, static::v($str, $nGroups, $perGroup, $sep));
                break;

            case "vv":
                fwrite(STDERR, static::vv($str, $nGroups, $perGroup, $sep));
                break;

            case "vvv":
                fwrite(STDERR, static::vvv($str, $nGroups, $perGroup, $sep));
                break;

            default:
                simple:
                $addPrefix = function($v) { return "0x$v"; };
                fwrite(STDERR, implode($sep, array_map($addPrefix, str_split(bin2hex($str), 2))));
        }
    }

    /**
     * 简单格式
     * @param string $str
     * @param int $nCols n columns
     * @param int $nHexs n hexs per column
     * @param string $sep separation between column
     * @return string
     */
    public static function v($str, $nCols = 16, $nHexs = 2, $sep = " ")
    {
        $nHexs = $nHexs % 2 === 0 ? $nHexs : $nHexs + 1;
        $hexLines = str_split(bin2hex($str), $nCols * $nHexs);

        $buffer = "";
        foreach ($hexLines as $i => $line) {
            $buffer .= static::split($line, $nHexs, $sep) . PHP_EOL;
        }

        return $buffer;
    }

    /**
     * 上下对照格式
     * @param string $str
     * @param int $nCols n columns
     * @param int $nHexs n hexs per column
     * @param string $sep separation between column
     * @param string $placeholder placeholder for invisible char
     * @return string
     */
    public static function vv($str, $nCols = 16, $nHexs = 2, $sep = " ", $placeholder = ".")
    {
        // 两个hex一个char, 必须凑成偶数
        $nHexs = $nHexs % 2 === 0 ? $nHexs : $nHexs + 1;
        $halfPerGroup = $nHexs / 2;

        $hexLines = str_split(bin2hex($str), $nCols * $nHexs);
        $charLines = str_split(static::bin($str, $placeholder), $nCols * $halfPerGroup);

        $buffer = "";
        foreach ($hexLines as $i => $line) {
            $buffer .= static::split($line, $nHexs, $sep) . PHP_EOL;
            $buffer .= static::split($charLines[$i], $halfPerGroup, str_repeat(" ", $halfPerGroup) . $sep) . PHP_EOL;
        }

        return $buffer;
    }

    /**
     * tcpdump格式
     * @param string $str
     * @param int $nCols n columns
     * @param int $nHexs n hexs per column
     * @param string $sep separation between column
     * @param string $placeholder placeholder for invisible char
     * @return string
     */
    public static function vvv($str, $nCols = 16, $nHexs = 2, $sep = " ", $placeholder = ".")
    {
        // 两个hex一个char, 必须凑成偶数
        $nHexs = $nHexs % 2 === 0 ? $nHexs : $nHexs + 1;
        $halfPerGroup = $nHexs / 2;

        $hexLines = str_split(bin2hex($str), $nCols * $nHexs);
        $charLines = str_split(static::bin($str, $placeholder), $nCols * $halfPerGroup);

        $lineHexWidth = $nCols * $nHexs + strlen($sep) * ($nCols - 1);

        $buffer = "";

        $offset = 0;
        foreach ($hexLines as $i => $line) {
            $hexs = static::split($line, $nHexs, $sep);
            $chars = $charLines[$i];

            $buffer .= sprintf("0x%06s: %-{$lineHexWidth}s  %s" . PHP_EOL, dechex($offset), $hexs, $chars);
            $offset += $nCols;
        }

        return $buffer;
    }

    private static function split($str, $len, $sep)
    {
        return implode($sep, str_split($str, $len));
    }

    private static function bin($str, $placeholder = ".")
    {
        static $from = "";
        static $to = "";

        if ($from == "") {
            for ($char = 0; $char <= 0xFF; $char++) {
                $from .= chr($char);
                $to .= ($char >= 0x20 && $char <= 0x7E) ? chr($char) : $placeholder;
            }
        }

        return strtr($str, $from, $to);
    }
}

/**
 *       ╔╦╗┌─┐┬─┐┌┬┐┬┌┐┌┌─┐┬
 * Class  ║ ├┤ ├┬┘│││││││├─┤│
 *        ╩ └─┘┴└─┴ ┴┴┘└┘┴ ┴┴─┘
 * @author xiaofeng
 *
 * ANSI/VT100 Terminal Control Escape Sequences
 * @standard http://www.termsys.demon.co.uk/vtansi.htm
 *
 */
class Terminal
{
    const ESC          = "\033";

    const BRIGHT       = 1;
    const DIM          = 2;
    const UNDERSCORE   = 4;
    const BLINK        = 5;
    const REVERSE      = 7;
    const HIDDEN       = 8;

    const FG_BLACK     = 30;
    const FG_RED       = 31;
    const FG_GREEN     = 32;
    const FG_YELLOW    = 33;
    const FG_BLUE      = 34;
    const FG_MAGENTA   = 35;
    const FG_CYAN      = 36;
    const FG_WHITE     = 37;

    const BG_BLACK     = 40;
    const BG_RED       = 41;
    const BG_GREEN     = 42;
    const BG_YELLOW    = 43;
    const BG_BLUE      = 44;
    const BG_MAGENTA   = 45;
    const BG_CYAN      = 46;
    const BG_WHITE     = 47;

    public static function format($text, ...$attrs)
    {
        // $text = addslashes($text);
        $resetAll = static::ESC . "[0m";
        $attrStr = implode(";", array_map("intval", $attrs));
        return static::ESC . "[{$attrStr}m{$text}" . $resetAll;
    }

    /**
     * Display
     * @param string $text
     * @param array $attrs
     *
     * Set Attribute Mode Format:
     *  <ESC>[{attr1};...;{attrn}m
     * e.g. \033[4;34;mhello 蓝色下划线文件hello
     * \033[0m
     */
    public static function put($text, ...$attrs) {
        echo static::format($text, ...$attrs);
    }

    public static function error($text, ...$attrs)
    {
        fprintf(STDERR, static::format($text, ...$attrs));
    }
}

function sys_echo($s) {
    $time = date("H:i:s", time());
    echo "$time $s\n";
}

function sys_abort($s)
{
    Terminal::error($s, Terminal::FG_RED, Terminal::BRIGHT);
    fprintf(STDERR, "\n\n");
    exit(1);
}

function is_big_endian()
{
    // return bin2hex(pack("L", 0x12345678)[0]) === "12";
    // L ulong 32，machine byte order
    return ord(pack("H2", bin2hex(pack("L", 0x12345678)))) === 0x12;
}


// for ide helper
if (!function_exists("nova_decode")) {
    /**
     * nova协议解包
     *
     * @since 2.0.0
     *
     * @param string $buf 二进制字符串
     * @param string &$service_name 服务名
     * @param string &$method_name 方法名
     * @param string &$ip
     * @param int &$port
     * @param int &$seq_no
     * @param string &$attach 附加字段 通常为json编码字符串
     * @param string &$data nova body
     * @return bool
     */
    function nova_decode($buf, &$service_name, &$method_name, &$ip, &$port, &$seq_no, &$attach, &$data) { return false; }
}

// 参考资料:
// 1. pcap文件格式: https://wiki.wireshark.org/Development/LibpcapFileFormat
// 2. pcap文件格式: https://www.zybuluo.com/natsumi/note/80231
// 3. 以太网帧格式: http://www.ituring.com.cn/article/42619
// 4. http://www.10tiao.com/html/254/201612/2648945706/1.html
// 5. linktypes: http://www.tcpdump.org/linktypes.html
// 6. 113-special Linux “cooked” capture: http://www.tcpdump.org/linktypes/LINKTYPE_LINUX_SLL.html
// 7. man tcpdump @ map
/*
Capturing TCP packets with particular flag combinations (SYN-ACK, URG-ACK, etc.)

       There are 8 bits in the control bits section of the TCP header:

              CWR | ECE | URG | ACK | PSH | RST | SYN | FIN

       Let's  assume  that  we want to watch packets used in establishing a TCP connection.  Recall that TCP uses a 3-way handshake protocol when it initializes a
       new connection; the connection sequence with regard to the TCP control bits is

              1) Caller sends SYN
              2) Recipient responds with SYN, ACK
              3) Caller sends ACK

       Now we're interested in capturing packets that have only the SYN bit set (Step 1).  Note that we don't want packets from step 2  (SYN-ACK),  just  a  plain
       initial SYN.  What we need is a correct filter expression for tcpdump.

       Recall the structure of a TCP header without options:

        0                            15                              31
       -----------------------------------------------------------------
       |          source port          |       destination port        |
       -----------------------------------------------------------------
       |                        sequence number                        |
       -----------------------------------------------------------------
       |                     acknowledgment number                     |
       -----------------------------------------------------------------
       |  HL   | rsvd  |C|E|U|A|P|R|S|F|        window size            |
       -----------------------------------------------------------------
       |         TCP checksum          |       urgent pointer          |
       -----------------------------------------------------------------

       A  TCP header usually holds 20 octets of data, unless options are present.  The first line of the graph contains octets 0 - 3, the second line shows octets
       4 - 7 etc.

       Starting to count with 0, the relevant TCP control bits are contained in octet 13:

        0             7|             15|             23|             31
       ----------------|---------------|---------------|----------------
       |  HL   | rsvd  |C|E|U|A|P|R|S|F|        window size            |
       ----------------|---------------|---------------|----------------
       |               |  13th octet   |               |               |

       Let's have a closer look at octet no. 13:

                       |               |
                       |---------------|
                       |C|E|U|A|P|R|S|F|
                       |---------------|
                       |7   5   3     0|

       These are the TCP control bits we are interested in.  We have numbered the bits in this octet from 0 to 7, right to left, so the PSH bit is bit  number  3,
       while the URG bit is number 5.

       Recall that we want to capture packets with only SYN set.  Let's see what happens to octet 13 if a TCP datagram arrives with the SYN bit set in its header:

                       |C|E|U|A|P|R|S|F|
                       |---------------|
                       |0 0 0 0 0 0 1 0|
                       |---------------|
                       |7 6 5 4 3 2 1 0|

       Looking at the control bits section we see that only bit number 1 (SYN) is set.

       Assuming that octet number 13 is an 8-bit unsigned integer in network byte order, the binary value of this octet is

              00000010

       and its decimal representation is

          7     6     5     4     3     2     1     0
       0*2 + 0*2 + 0*2 + 0*2 + 0*2 + 0*2 + 1*2 + 0*2  =  2

       We're almost done, because now we know that if only SYN is set, the value of the 13th octet in the TCP header, when interpreted as a 8-bit unsigned integer
       in network byte order, must be exactly 2.

       This relationship can be expressed as
              tcp[13] == 2

       We can use this expression as the filter for tcpdump in order to watch packets which have only SYN set:
              tcpdump -i xl0 tcp[13] == 2

       The expression says "let the 13th octet of a TCP datagram have the decimal value 2", which is exactly what we want.

       Now,  let's assume that we need to capture SYN packets, but we don't care if ACK or any other TCP control bit is set at the same time.  Let's see what hap-
       pens to octet 13 when a TCP datagram with SYN-ACK set arrives:

            |C|E|U|A|P|R|S|F|
            |---------------|
            |0 0 0 1 0 0 1 0|
            |---------------|
            |7 6 5 4 3 2 1 0|

       Now bits 1 and 4 are set in the 13th octet.  The binary value of octet 13 is

                   00010010

       which translates to decimal

          7     6     5     4     3     2     1     0
       0*2 + 0*2 + 0*2 + 1*2 + 0*2 + 0*2 + 1*2 + 0*2   = 18

       Now we can't just use 'tcp[13] == 18' in the tcpdump filter expression, because that would select only those packets that have SYN-ACK set, but  not  those
       with only SYN set.  Remember that we don't care if ACK or any other control bit is set as long as SYN is set.

       In  order  to  achieve our goal, we need to logically AND the binary value of octet 13 with some other value to preserve the SYN bit.  We know that we want
       SYN to be set in any case, so we'll logically AND the value in the 13th octet with the binary value of a SYN:

                 00010010 SYN-ACK              00000010 SYN
            AND  00000010 (we want SYN)   AND  00000010 (we want SYN)
                 --------                      --------
            =    00000010                 =    00000010

       We see that this AND operation delivers the same result regardless whether ACK or another TCP control bit is set.  The decimal representation  of  the  AND
       value as well as the result of this operation is 2 (binary 00000010), so we know that for packets with SYN set the following relation must hold true:

              ( ( value of octet 13 ) AND ( 2 ) ) == ( 2 )

       This points us to the tcpdump filter expression
                   tcpdump -i xl0 'tcp[13] & 2 == 2'

       Some  offsets and field values may be expressed as names rather than as numeric values. For example tcp[13] may be replaced with tcp[tcpflags]. The follow-
       ing TCP flag field values are also available: tcp-fin, tcp-syn, tcp-rst, tcp-push, tcp-act, tcp-urg.

       This can be demonstrated as:
                   tcpdump -i xl0 'tcp[tcpflags] & tcp-push != 0'

       Note that you should use single quotes or a backslash in the expression to hide the AND ('&') special character from the shell.
*/
