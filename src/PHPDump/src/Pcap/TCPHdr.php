<?php

namespace Minimalism\PHPDump\Pcap;


use Minimalism\PHPDump\Buffer\Buffer;

/**
 * Class TcpHdr
 * @package Minimalism\PHPDump\Pcap
 *
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
 */
class TCPHdr
{
    const SIZE = 20;

    public $source_port;
    public $destination_port;
    public $seq;
    public $ack;
    public $offset;
    public $window;
    public $checksum;
    public $urgent;
    public $flag_NS;
    public $flag_CWR;
    public $flag_ECE;
    public $flag_URG;
    public $flag_ACK;
    public $flag_PSH;
    public $flag_RST;
    public $flag_SYN;
    public $flag_FIN;

    public static function unpack(Buffer $recordBuffer, Pcap $pcap)
    {
        if ($recordBuffer->readableBytes() < self::SIZE) {
            sys_abort("buffer is too small to read tcp header");
        }

        // HL, HLEN, offset, 数据偏移量, 4位包括TCP头大小, 指示何处数据开始
        $tcp_hdr = [
            $pcap->u16 . "source_port/",
            $pcap->u16 . "destination_port/",
            $pcap->u32 . "seq/",
            $pcap->u32 . "ack/",
            $pcap->uC  . "tmp1/",
            $pcap->uC  . "tmp2/",
            $pcap->u16 . "window/",
            $pcap->u16 . "checksum/",
            $pcap->u16 . "urgent"
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
        $options = $recordBuffer->read($options_len); // ignoring options

        $self = new static;

        $self->source_port = $tcp["source_port"];
        $self->destination_port = $tcp["destination_port"];
        $self->seq = $tcp["seq"];
        $self->ack = $tcp["ack"];
        $self->offset = $tcp["offset"];
        $self->window = $tcp["window"];
        $self->checksum = $tcp["checksum"];
        $self->urgent = $tcp["urgent"];
        $self->flag_NS = $tcp["flag_NS"];
        $self->flag_CWR = $tcp["flag_CWR"];
        $self->flag_ECE = $tcp["flag_ECE"];
        $self->flag_URG = $tcp["flag_URG"];
        $self->flag_ACK = $tcp["flag_ACK"];
        $self->flag_PSH = $tcp["flag_PSH"];
        $self->flag_RST = $tcp["flag_RST"];
        $self->flag_SYN = $tcp["flag_SYN"];
        $self->flag_FIN = $tcp["flag_FIN"];

        return $self;
    }
}