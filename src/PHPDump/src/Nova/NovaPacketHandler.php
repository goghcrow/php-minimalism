<?php

namespace Minimalism\PHPDump\Nova;


use Minimalism\PHPDump\Buffer\BufferFactory;
use Minimalism\PHPDump\Buffer\Hex;
use Minimalism\PHPDump\Pcap\IPHdr;
use Minimalism\PHPDump\Pcap\LinuxSLLHdr;
use Minimalism\PHPDump\Pcap\NovaHdr;
use Minimalism\PHPDump\Pcap\PacketHdr;
use Minimalism\PHPDump\Pcap\TCPHdr;
use Minimalism\PHPDump\Thrift\TCodec;
use Minimalism\PHPDump\Thrift\TMessageType;
use Minimalism\PHPDump\Util\T;

class NovaPacketHandler
{
    public $filter;
    public $novaCopy;

    /**
     * NovaPacketHandler constructor.
     * @param callable|null $filter
     * @param callable $novaCopy
     */
    public function __construct($filter = null, $novaCopy = null)
    {
        $this->filter = $filter;
        $this->novaCopy = $novaCopy;
    }

    public function __invoke(NovaHdr $novaHdr,
                             PacketHdr $rec_hdr,
                             LinuxSLLHdr $linux_sll,
                             IPHdr $ip_hdr,
                             TCPHdr $tcp_hdr)
    {
        $seq = $novaHdr->seq;
        $service = $novaHdr->service;
        $method = $novaHdr->method;
        $ip = $novaHdr->ip;
        $port = $novaHdr->port;
        $attach = $novaHdr->attach;
        $thriftBin = $novaHdr->thriftBin;

        if ($filter = $this->filter) {
            if ($filter($service, $method) === false) {
                return;
            }
        }

        $isHeartbeat = NovaPacketFilter::isHeartbeat($service, $method);

        $srcIp = $ip_hdr->source_ip;
        $dstIp = $ip_hdr->destination_ip;
        $srcPort = $tcp_hdr->source_port;
        $dstPort = $tcp_hdr->destination_port;

        $_ip = T::format($ip, T::BRIGHT);
        $_port = T::format($port, T::BRIGHT);
        $_seq = T::format($seq, T::BRIGHT);
        $_service = T::format($service, T::FG_GREEN);
        $_method = T::format($method, T::FG_GREEN);
        $_attach = T::format($attach, T::DIM);
        $_src = T::format("$srcIp:$srcPort", T::BRIGHT);
        $_dst = T::format("$dstIp:$dstPort", T::BRIGHT);

        $sec = $rec_hdr["ts_sec"];
        $usec = $rec_hdr["ts_usec"];

        // 存在nova_ip和nova_port是因为可能发送接收的地址是proxy，nova_ip这里是服务真实ip
        // !!! 这里显示的时间必须是 pcap包时间戳
        sys_echo("$_src > $_dst, nova_ip $_ip, nova_port $_port, nova_seq $_seq", $sec, $usec);
        sys_echo("service $_service, method $_method", $sec, $usec);

        if ($attach && $attach !== "{}") {
            sys_echo("attach $_attach", $sec, $usec);
        }

        if (!$isHeartbeat) {
            if (NovaApp::$enable) {
                list($type, $args) = NovaApp::dumpThrift($service, $method, $thriftBin);

                if ($this->novaCopy) {
                    $copy = $this->novaCopy;
                    $copy($type, $dstIp, $dstPort, $service, $method, $args);
                }
            } else {
                $buffer = BufferFactory::make();
                $buffer->write($thriftBin);
                $tCodec = new TCodec($buffer);
                list($type, $name, $seqId, $fields) = $tCodec->decode();
                $msgType = TMessageType::getName($type);
                // TODO 调整颜色，添加-v -vv -vvv参数
                echo "\n#$seqId $msgType $name\n";
                echo json_encode($fields), "\n";
                Hex::dump($thriftBin, "vvv/8/6");
            }
        }

        echo "\n";
    }
}