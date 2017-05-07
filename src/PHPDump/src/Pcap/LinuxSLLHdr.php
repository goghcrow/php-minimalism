<?php

namespace Minimalism\PHPDump\Pcap;


use Minimalism\PHPDump\Buffer\Buffer;

/**
 * Class LinuxSLL
 * Linux "cooked" capture encapsulation.
 * @package Minimalism\PHPDump\Pcap
 *
 * Octet 8位元组 8个二进制位
 *
 * Byte 通常情也表示8个bit
 * Byte 表示CPU可以独立的寻址的最小内存单位（不过通过移位和逻辑运算，CPU也可以寻址到某一个单独的bit）
 *
 * @see http://www.tcpdump.org/linktypes/LINKTYPE_LINUX_SLL.html
 * @see https://wiki.wireshark.org/SLL
 *
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
 *
 * struct linux_sll {
 *      u16   packet_type,  Packet_* describing packet origins
 *      dev_type,     ARPHDR_* from net/if_arp.h
 *      addr_len;     length of contents of 'addr' field
 *      u8    addr[8];
 *      u16   eth_type;     same as ieee802_3 'lentype' field, with additional Eth_Type_* exceptions
 * };
 * @param Buffer $recordBuffer
 * @return array
 */
class LinuxSLLHdr
{
    const MIN_SIZE = 16;

    const IPV4 = 0x800;

    public $packet_type;
    public $arphdr_type;
    public $addr_len;
    public $addr;
    public $eth_type;

    public static function unpack(Buffer $recordBuffer, Pcap $pcap)
    {
        if ($recordBuffer->readableBytes() < self::MIN_SIZE) {
            sys_abort("buffer is too small to read linux ssl header");
        }

        $linux_sll_fmt = [
            $pcap->u16 . "packet_type/",
            // The packet type field is in network byte order (big-endian); it contains a value that is one of:
            // 0, if the packet was specifically sent to us by somebody else;
            // 1, if the packet was broadcast by somebody else;
            // 2, if the packet was multicast, but not broadcast, by somebody else;
            // 3, if the packet was sent to somebody else by somebody else;
            // 4, if the packet was sent by us.

            $pcap->u16 . "arphdr_type/",
            // The ARPHRD_ type field is in network byte order; it contains a Linux ARPHRD_ value for the link-layer device type.

            $pcap->u16 . "address_length/",
            // The link-layer address length field is in network byte order;
            // it contains the length of the link-layer address of the sender of the packet. That length could be zero.

            $pcap->u32 . "address_1/",
            $pcap->u32 . "address_2/",
            // The link-layer address field contains the link-layer address of the sender of the packet;
            // the number of bytes of that field that are meaningful is specified by the link-layer address length field.
            // If there are more than 8 bytes, only the first 8 bytes are present, and if there are fewer than 8 bytes, there are padding bytes after the address to pad the field to 8 bytes.

            $pcap->u16 . "type",
            // The protocol type field is in network byte order; it contains an Ethernet protocol type, or one of:
            // 1, if the frame is a Novell 802.3 frame without an 802.2 LLC header;
            // 4, if the frame begins with an 802.2 LLC header.
            // ethernet type ...
            //
            // dechex(2048) === 0x800 -> Protocol IPV4
            // 0x806 ARP
            // ...
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
        if ($pcap->u32 === "N") {
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

        $self = new static;

        $self->packet_type = $linux_sll["packet_type"];
        $self->arphdr_type = $linux_sll["arphdr_type"];
        $self->addr_len = $linux_sll["address_length"];
        $self->addr = $linux_sll["address"]; // TODO
        $self->eth_type = $linux_sll["type"];

        return $self;
    }
}