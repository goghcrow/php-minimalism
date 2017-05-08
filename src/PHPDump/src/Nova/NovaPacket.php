<?php

namespace Minimalism\PHPDump\Nova;


use Minimalism\PHPDump\Buffer\Hex;
use Minimalism\PHPDump\Pcap\Connection;
use Minimalism\PHPDump\Pcap\Packet;
use Minimalism\PHPDump\Thrift\ThriftPacket;
use Minimalism\PHPDump\Thrift\TMessageType;
use Minimalism\PHPDump\Util\T;

class NovaPacket extends Packet
{
    public $service;
    public $method;
    public $ip;
    public $port;
    public $seq;
    public $attach;
    public $thriftBin;


    public function analyze(Connection $connection)
    {
        $seq = $this->seq;
        $service = $this->service;
        $method = $this->method;
        $ip = $this->ip;
        $port = $this->port;
        $attach = $this->attach;
        $thriftBin = $this->thriftBin;

        $isHeartbeat = NovaPacketFilter::isHeartbeat($service, $method);

        $srcIp = $connection->IPHdr->source_ip;
        $dstIp = $connection->IPHdr->destination_ip;
        $srcPort = $connection->TCPHdr->source_port;
        $dstPort = $connection->TCPHdr->destination_port;

        $_ip = T::format($ip, T::BRIGHT);
        $_port = T::format($port, T::BRIGHT);
        $_seq = T::format($seq, T::BRIGHT);
        $_service = T::format($service, T::FG_GREEN);
        $_method = T::format($method, T::FG_GREEN);
        $_attach = T::format($attach, T::DIM);
        $_src = T::format("$srcIp:$srcPort", T::BRIGHT);
        $_dst = T::format("$dstIp:$dstPort", T::BRIGHT);

        $sec = $connection->recordHdr->ts_sec;
        $usec = $connection->recordHdr->ts_usec;

        // 存在nova_ip和nova_port是因为可能发送接收的地址是proxy，nova_ip这里是服务真实ip
        // !!! 这里显示的时间必须是 pcap包时间戳
        sys_echo("$_src > $_dst, nova_ip $_ip, nova_port $_port, nova_seq $_seq", $sec, $usec);
        sys_echo("service $_service, method $_method", $sec, $usec);

        if ($attach && $attach !== "{}") {
            sys_echo("attach $_attach", $sec, $usec);
        }

        if ($isHeartbeat) {

        } else {
            $thriftPacket = ThriftPacket::unpack($thriftBin);
            $fieldsJson = T::format(json_encode($thriftPacket->fields), T::DIM);

            // nova包头已经包含了 thrift 包中的name, 这里不予显示
            // $thriftPacket->name
            // thrift 包中的 seqId 没有使用, 此处不予显示
            // $thriftPacket->seqId

            if (NovaLocalCodec::$enable) {
                $args = NovaLocalCodec::dumpThrift($this, $thriftPacket);
                sys_echo($fieldsJson);
                echo "\n";
                return [$this,  $thriftPacket, $args];
            } else {
                // TODO -v Hex::dump($thriftBin, "vvv/8/6");
                $msgType = T::format(TMessageType::getName($thriftPacket->type), T::FG_YELLOW, T::BRIGHT);
                sys_echo($msgType);
                sys_echo($fieldsJson);
            }
        }

        echo "\n";
        return null;
    }
}