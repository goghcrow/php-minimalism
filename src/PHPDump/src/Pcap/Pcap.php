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
     * @var Buffer $buffer pcap buffer
     */
    public $buffer;

    /**
     * @var PcapHdr
     */
    public $pcap_hdr;

    public $novaPacketHandler;

    /**
     * @var Buffer[] $novaConnsBuffers
     *
     * srcIp:scrPort-dstIp:dstPort => MemoryBuffer
     * 每条虚拟连接一个Buffer, 用于处理粘包
     */
    public $novaConnsBuffers = [];

    // TODO 计算 tcp segment 真实长度, 计算是否捕获完整
    public $lastSeqLen = [];

    public function __construct(Buffer $buffer, callable $novaPacketHandler)
    {
        $this->buffer = $buffer;
        $this->novaPacketHandler = $novaPacketHandler;
    }

    private function unpackGlobalHdr()
    {
        if ($this->pcap_hdr === null) {
            $this->pcap_hdr = PcapHdr::unpack($this->buffer, $this);
        }
    }

    private function unpackPacketHdr()
    {
        return PacketHdr::unpack($this->buffer, $this);
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

    private function unpackNova(Buffer $connBuffer)
    {
        return NovaHdr::unpack($connBuffer, $this);
    }

    private function isRecordReceiveCompleted()
    {
        return PacketHdr::isReceiveCompleted($this->buffer, $this);
    }

    private function isNovaReceiveCompleted(Buffer $connBuffer)
    {
        return NovaHdr::isReceiveCompleted($connBuffer, $this);
    }

    public function captureLoop()
    {
        $this->unpackGlobalHdr();

        /* Loop reading packets */
        while ($this->isRecordReceiveCompleted()) {

            /* 1. Read packet header */
            $rec_hdr = $this->unpackPacketHdr();

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
            if (!isset($this->novaConnsBuffers[$connKey])) {
                // fitler nova packet： buffer size < 37时候， 这里的判断有点问题，
                if (NovaHdr::detect($recordBuffer)) {
                    $this->novaConnsBuffers[$connKey] = BufferFactory::make();
                } else {
                    continue;
                }
            }

            /** @var Buffer $connBuffer */
            $connBuffer = $this->novaConnsBuffers[$connKey];

            // move bytes
            $connBuffer->write($recordBuffer->readFull());

            // 这里有个问题: 如果tcpdump 捕获的数据不全
            // 需要使用对端回复的ip分节的 ack-1 来确认此条ip分节的长度
            // 从而检查到接受数据是否有问题, 这里简化处理, 没有检测

            while (true) {
                if (!$this->isNovaReceiveCompleted($connBuffer)) {
                    continue 2;
                }

                // TODO refactor
                $novaPacket = $this->unpackNova($connBuffer);
                try {
                    $fn = $this->novaPacketHandler;
                    $fn($novaPacket, $rec_hdr, $linux_sll, $ip_hdr, $tcp_hdr);
                } catch (\Exception $ex) {
                    echo $ex, "\n";
                    sys_abort("handle nova pack fail");
                }
            }
        }
    }
}