<?php

namespace Minimalism\PHPDump\Pcap;


use Minimalism\PHPDump\Buffer\BufferFactory;

class Connection
{
    public $srcIP;
    public $dstIP;
    public $srcPort;
    public $dstPort;

    public $recordHdr;
    public $linuxSLLHdr;
    public $IPHdr;
    public $TCPHdr;

    public $buffer;

    public $protocol;

    public function __construct(
        $srcIP,
        $srcPort,
        $dstIP,
        $dstPort,
        Protocol $protocol,
        RecordHdr $recordHdr,
        LinuxSLLHdr $linuxSLLHdr,
        IPHdr $IPHdr,
        TCPHdr $TCPHdr
    )
    {
        $this->srcIP = $srcIP;
        $this->srcPort = $srcPort;
        $this->dstIP = $dstIP;
        $this->dstPort = $dstPort;

        $this->protocol = $protocol;

        $this->recordHdr = $recordHdr;
        $this->linuxSLLHdr = $linuxSLLHdr;
        $this->IPHdr = $IPHdr;
        $this->TCPHdr = $TCPHdr;

        $this->buffer = BufferFactory::make();
    }

    public function loopAnalyze()
    {
        // 这里有个问题: 如果tcpdump 捕获的数据不全
        // 需要使用对端回复的ip分节的 ack-1 来确认此条ip分节的长度
        // 从而检查到接受数据是否有问题, 这里简化处理, 没有检测

        while (true) {
            if ($this->protocol->isReceiveCompleted($this->buffer)) {

                $packet = $this->protocol->unpack($this->buffer);

                if ($packet->beforeAnalyze()) {
                    try {
                        $copyArgs = $packet->analyze($this);
                        if ($copyArgs !== null) {
                            $packet->afterAnalyze(...$copyArgs);
                        }
                    } catch (\Exception $ex) {
                        echo $ex, "\n";
                        $protocolName = $this->protocol->getName();
                        sys_abort("protocol $protocolName pack analyze fail");
                    }
                }

            } else {
                break;
            }
        }
    }
}