<?php

namespace Minimalism\PHPDump\Pcap;


use Minimalism\PHPDump\Buffer\Buffer;
use Minimalism\PHPDump\Buffer\BufferFactory;

/**
 * Class Connection
 * @package Minimalism\PHPDump\Pcap
 *
 * 单方向连接
 */
class Connection
{
    private $pcap;

    /**
     * @var RecordHdr
     */
    public $recordHdr;

    /**
     * @var LinuxSLLHdr
     */
    public $linuxSLLHdr;

    /**
     * @var IPHdr
     */
    public $IPHdr;

    /**
     * @var TCPHdr
     */
    public $TCPHdr;

    /**
     * @var Buffer
     */
    public $buffer;

    /**
     * @var Dissector
     */
    public $dissector;

    /**
     * 当前连接未解析完成的包
     *
     * @var PDU $currentPacket
     *
     * 有的协议(比如http) 在isReceiveCompleted已经尝试parser一次
     * 可以暂时保存起来
     * 然后unpack中可以直接获取使用
     */
    public $currentPacket;

    /**
     * @var static
     */
    public $reverseConnection;

    /**
     * 协议解析状态机的状态
     * 为了数据处理方便，这里连接是单向的
     * 应该抽象一个双向的连接，这里的状态是针对两个Connection对象，
     *
     * @var mixed
     */
    public $state;

    /**
     * @var callable[]
     */
    private $events;

    const EVT_CLOSE = 1;
    const EVT_RESPONSE = 2;

    public function __construct(Pcap $pcap, RecordHdr $recordHdr, LinuxSLLHdr $linuxSLLHdr, IPHdr $IPHdr, TCPHdr $TCPHdr)
    {
        $this->pcap = $pcap;
        $this->recordHdr = $recordHdr;
        $this->linuxSLLHdr = $linuxSLLHdr;
        $this->IPHdr = $IPHdr;
        $this->TCPHdr = $TCPHdr;

        $this->buffer = BufferFactory::make();
    }

    public function setDissector(Dissector $dissector)
    {
        $this->dissector = $dissector;
    }

    public function reverseWith(Connection $reverseConnection)
    {
        $this->reverseConnection = $reverseConnection;
    }

    public function isDetected()
    {
        return $this->dissector !== null;
    }

    public function on($evt, callable $cb, $once = false)
    {
        $this->events[$evt] = [$cb, $once];
    }

    public function trigger($evt, ...$args)
    {
        if (isset($this->events[$evt])) {
            list($cb, $once) = $this->events[$evt];

            try {
                $cb(...$args);
            } catch (\Exception $ex) {
                echo $ex, "\n";
            }

            if ($once) {
                unset($this->events[$evt]);
            }
        }
    }

    public function loopDissect()
    {
        // 这里有个问题: 如果tcpdump 捕获的数据不全
        // 需要使用对端回复的ip分节的 ack-1 来确认此条ip分节的长度
        // 从而检查到接受数据是否有问题, 这里简化处理, 没有检测

        while (true) {
            if ($this->dissector->isReceiveCompleted($this)) {
                $this->doDissect();
            } else {
                break;
            }
        }
    }

    public function doDissect()
    {
        $pdu = $this->dissector->dissect($this);

        if ($pdu instanceof PDU) {
            if ($pdu->preInspect()) {
                try {
                    $pdu->inspect($this);
                    $pdu->postInspect();
                } catch (\Exception $ex) {
                    $dissector = $this->dissector->getName();
                    sys_abort("dissector $dissector dissect fail");
                    sys_error($ex->__toString());
                }
            }
        }
    }

    public function close()
    {
        $this->pcap->closeConnection($this);
    }

    public function __toString()
    {
        return static::makeKey($this->IPHdr, $this->TCPHdr);
    }

    public static function makeKey(IPHdr $ipHdr, TCPHdr $tcpHdr, $reverse = false)
    {
        $srcIp = $ipHdr->source_ip;
        $dstIp = $ipHdr->destination_ip;
        $srcPort = $tcpHdr->source_port;
        $dstPort = $tcpHdr->destination_port;

        if ($reverse) {
            return "$dstIp:$dstPort > $srcIp:$srcPort";
        } else {
            return "$srcIp:$srcPort > $dstIp:$dstPort";
        }
    }
}