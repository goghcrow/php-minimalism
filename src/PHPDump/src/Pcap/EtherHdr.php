<?php

namespace Minimalism\PHPDump\Pcap;


use Minimalism\PHPDump\Buffer\Buffer;


/**
 * Class EtherHdr
 * @package Minimalism\PHPDump\Pcap
 *
 * http://www.ituring.com.cn/article/42619
 * 它总共占14个字节。分别是6个字节的目标MAC地址、6个字节的源MAC地址以及2个字节的上层协议类型。
 */
class EtherHdr
{
    const SIZE = 14;

    public $destination_mac;
    public $source_mac;
    public $ethertype;

    public static function unpack(Buffer $recordBuffer, Pcap $pcap)
    {
        if ($recordBuffer->readableBytes() < self::SIZE) {
            sys_abort("buffer is too small to read ether header");
        }

        $self = new static;

        $self->destination_mac = bin2hex($recordBuffer->read(6));
        $self->source_mac = bin2hex($recordBuffer->read(6));

        $r = unpack($pcap->u16 . "ethertype", $recordBuffer->read(2));
        if(!isset($r["ethertype"])) {
            sys_abort("malformed ether header");
        }
        $self->ethertype = $r["ethertype"];

        return $self;
    }
}