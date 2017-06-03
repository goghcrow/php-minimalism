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

    // TODO ！！！！！ 计算 tcp segment 真实长度, 计算是否捕获完整
    private $lastSeqLen = [];

    /**
     * @var Dissector[]
     */
    public static $dissectors = [];

    public function __construct(Buffer $buffer)
    {
        $this->buffer = $buffer;
    }

    public static function registerDissector(Dissector $dissector)
    {
        static::$dissectors[] = $dissector;
    }

    /**
     * 关闭单向连接
     * @param Connection $connection
     */
    public function closeConnection(Connection $connection)
    {
        unset($this->connections[strval($connection)]);
    }

    public function captureLoop()
    {
        if ($this->pcap_hdr === null) {
            $this->pcap_hdr = PcapHdr::unpack($this->buffer, $this);
        }

        /* Loop reading packets */
        while (RecordHdr::isReceiveCompleted($this->buffer, $this)) {

            /* 1. Read packet header */
            $rec_hdr = RecordHdr::unpack($this->buffer, $this);

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
            $linux_sll = LinuxSLLHdr::unpack($recordBuffer, $this);;
            // 过滤非IPV4
            if ($linux_sll->eth_type !== LinuxSLLHdr::IPV4) {
                continue;
            }

            /* 4. unpack ip datagram */
            $ip_hdr = IPHdr::unpack($recordBuffer, $this);
            if ($ip_hdr->version !== IPHdr::VER4) { // 过滤非IPV4
                continue;
            }
            if ($ip_hdr->protocol !== IPHdr::PROTO_TCP) { // 过滤非TCP包
                continue;
            }

            /* 5. unpack tcp segment */
            $tcp_hdr = TCPHdr::unpack($recordBuffer, $this);

            /* 6. detect and analyze protocol  */
            $connKey = Connection::makeKey($ip_hdr, $tcp_hdr);
            $reverseConnKey = Connection::makeKey($ip_hdr, $tcp_hdr, true);

            // trigger close event
            if ($tcp_hdr->flag_FIN) {
                $isContinue = $this->triggerCloseEvent($connKey, $reverseConnKey);
                if ($isContinue) {
                    continue;
                }
            }

            if ($recordBuffer->readableBytes() === 0) {
                continue;
            }

            if (isset($this->connections[$connKey])) {
                $connection = $this->connections[$connKey];
            } else {
                $connection = new Connection($this, $rec_hdr, $linux_sll, $ip_hdr, $tcp_hdr);
                $this->reverseWith($connection, $reverseConnKey);
            }

            $connection->buffer->write($recordBuffer->readFull());

            if ($connection->isDetected()) {
                $connection->loopDissect();
            } else {
                // 检查该连接应用层协议
                foreach (static::$dissectors as $dissector) {
                    $detectedState = $dissector->detect($connection);

                    switch ($detectedState) {
                        case Dissector::DETECTED:
                            $this->connections[$connKey] = $connection;
                            $connection->setDissector($dissector);
                            $connection->loopDissect();
                            break 2;

                        case Dissector::DETECT_WAIT:
                            // 暂时持有未检测到协议的连接
                            $this->connections[$connKey] = $connection;
                            break;

                        case Dissector::UNDETECTED:
                        default:
                            // do nothing 继续检测其他协议
                            break;
                    }
                }
            }
        }
    }

    private function reverseWith(Connection $connection, $reverseConnKey)
    {
        if (isset($this->connections[$reverseConnKey])) {
            $reverseConnection = $this->connections[$reverseConnKey];
            $connection->reverseWith($reverseConnection);
            $reverseConnection->reverseWith($connection);
        }
    }

    private function triggerCloseEvent($connKey, $reverseConnKey)
    {
        $isContinue = false;
        if (isset($this->connections[$connKey])) {
            $connection = $this->connections[$connKey];
            try {
                $connection->trigger(Connection::EVT_CLOSE);
            } catch (\Exception $e) {
                echo $e;
            }
            unset($this->connections[$connKey]); // 关闭之后移除连接
            $isContinue = true;
        }
        if (isset($this->connections[$reverseConnKey])) {
            $reverseConnection = $this->connections[$reverseConnKey];
            try {
                $reverseConnection->trigger(Connection::EVT_CLOSE);
            } catch (\Exception $e) {
                echo $e;
            }
            unset($this->connections[$reverseConnKey]); // 关闭之后移除连接
            $isContinue = true;
        }
        return $isContinue;
    }
}