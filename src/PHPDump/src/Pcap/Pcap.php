<?php

namespace Minimalism\PHPDump\Pcap;


use Minimalism\PHPDump\Buffer\Buffer;
use Minimalism\PHPDump\Buffer\BufferFactory;

class Pcap
{
    /**
     * @var string pcap order byte unsigned int format
     */
    public $u32;

    /**
     * @var string pcap order byte unsigned short format
     */
    public $u16;

    /**
     * @var string pcap order byte unsigned char format
     */
    public $uC;

    /**
     * @var PcapHdr
     */
    public $pcap_hdr;

    /**
     * @var Buffer $buffer pcap buffer
     */
    private $buffer;

    /**
     * @var Connection[]
     *
     * srcIp:scrPort-dstIp:dstPort => MemoryBuffer
     * 每条虚拟连接一个Buffer, 用于处理粘包
     */
    private $connections = [];

    // TODO 计算 tcp segment 真实长度, 计算是否捕获完整
    // private $lastSeqLen = [];

    /**
     * @var Protocol[]
     */
    public static $protocols = [];

    public function __construct(Buffer $buffer)
    {
        $this->buffer = $buffer;
    }

    public static function registerProtocol(Protocol $protocol)
    {
        static::$protocols[] = $protocol;
    }

    private function unpackGlobalHdr()
    {
        if ($this->pcap_hdr === null) {
            $this->pcap_hdr = PcapHdr::unpack($this->buffer, $this);
        }
    }

    private function unpackRecordHdr()
    {
        return RecordHdr::unpack($this->buffer, $this);
    }

    private function unpackLinuxSLL(Buffer $recordBuffer)
    {
        return LinuxSLLHdr::unpack($recordBuffer, $this);
    }

    private function unpackIpHdr(Buffer $recordBuffer)
    {
        return IPHdr::unpack($recordBuffer, $this);
    }

    private function unpackTcpHdr(Buffer $recordBuffer)
    {
        return TCPHdr::unpack($recordBuffer, $this);
    }

    private function isRecordReceiveCompleted()
    {
        return RecordHdr::isReceiveCompleted($this->buffer, $this);
    }

    public function captureLoop()
    {
        $this->unpackGlobalHdr();

        /* Loop reading packets */
        while ($this->isRecordReceiveCompleted()) {

            /* 1. Read packet header */
            $rec_hdr = $this->unpackRecordHdr();

            /* 2. Read packet raw data */
            $incl_len = $rec_hdr->incl_len;
            $record = $this->buffer->read($incl_len);
            if ($incl_len !== strlen($record)) {
                sys_abort("unexpected record length");
            }

            $recordBuffer = BufferFactory::make();
            $recordBuffer->write($record);

            // 传输层叫做段（segment），在网络层叫做数据报（datagram），在链路层叫做帧（frame）
            // 注意: 此处不是ether
            // $ether = $this->unpackEtherHdr();
            // 0x0800 ipv4
            // if ($ether["ethertype"] !== 0x0800) {
            //     return false; // continue;
            // }

            /* 3. unpack linux sll frame */
            $linux_sll = $this->unpackLinuxSLL($recordBuffer);
            // 过滤非IPV4
            if ($linux_sll->eth_type !== LinuxSLLHdr::IPV4) {
                continue;
            }

            /* 4. unpack ip datagram */
            $ip_hdr = $this->unpackIpHdr($recordBuffer);
            if ($ip_hdr->version !== IPHdr::VER4) { // 过滤非IPV4
                continue;
            }
            if ($ip_hdr->protocol !== IPHdr::PROTO_TCP) { // 过滤非TCP包
                continue;
            }

            /* 5. unpack tcp segment */
            $tcp_hdr = $this->unpackTcpHdr($recordBuffer);

            $srcIp = $ip_hdr->source_ip;
            $dstIp = $ip_hdr->destination_ip;
            $srcPort = $tcp_hdr->source_port;
            $dstPort = $tcp_hdr->destination_port;

            $connKey = "$srcIp:$srcPort-$dstIp:$dstPort";

            if (isset($this->connections[$connKey])) {
                $connection = $this->connections[$connKey];
                if ($recordBuffer->readableBytes() > 0) {
                    // move bytes
                    $connection->buffer->write($recordBuffer->readFull());
                    $connection->loopAnalyze();
                }
            } else {
                // 检查该连接应用层协议
                foreach (static::$protocols as $protocol) {
                    if ($protocol->detect($recordBuffer)) {
                        $this->connections[$connKey] = new Connection(
                            $srcIp, $srcPort, $dstIp, $dstPort,
                            $protocol,
                            $rec_hdr, $linux_sll, $ip_hdr, $tcp_hdr);
                        break;
                    }
                }
            }
        }
    }
}