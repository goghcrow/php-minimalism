<?php

namespace Minimalism\PHPDump\Pcap;


use Minimalism\PHPDump\Buffer\Buffer;

/**
 * Class PcapHdr
 * @package Minimalism\PHPDump\Pcap
 *
 * Packet structure
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
 * Description
 *
 * The packet type field is in network byte order (big-endian); it contains a value that is one of:
 *
 * 0, if the packet was specifically sent to us by somebody else;
 * 1, if the packet was broadcast by somebody else;
 * 2, if the packet was multicast, but not broadcast, by somebody else;
 * 3, if the packet was sent to somebody else by somebody else;
 * 4, if the packet was sent by us.
 * The ARPHRD_ type field is in network byte order; it contains a Linux ARPHRD_ value for the link-layer device type.
 *
 * typedef struct pcap_hdr_s {
 *      guint32 magic_number;   // magic number
 *      guint16 version_major;  // major version number
 *      guint16 version_minor;  // minor version number
 *      gint32  thiszone;       // GMT to local correction
 *      guint32 sigfigs;        // accuracy of timestamps
 *      guint32 snaplen;        // max length of captured packets, in octets
 *      guint32 network;        // data link type
 * } pcap_hdr_t;
 *
 * I4 I2 I2 i4 I4 I4 I4
 *
 * 注意: tcpdump 产生的是一种Linux “cooked” capture 的 linktype
 * network --> linktype
 * http://www.tcpdump.org/linktypes.html
 * 113         special Linux “cooked” capture
 * http://www.tcpdump.org/linktypes/LINKTYPE_LINUX_SLL.html
 *
 */
class PcapHdr
{
    const SIZE = 24;

    // http://www.tcpdump.org/linktypes.html
    const LINKTYPE_LINUX_SLL = 113;

    public $magic_number;
    public $version_major;
    public $version_minor;
    public $thiszone;
    public $sigfigs;
    public $snaplen;
    public $network;

    public static function unpack(Buffer $buffer, Pcap $pcap)
    {
        if ($buffer->readableBytes() < self::SIZE) {
            sys_abort("buffer is too small to read pcap header");
        }

        $u32 = is_big_endian() ? "V" : "N";
        $magic_number = unpack("{$u32}magic_number", $buffer->read(4))["magic_number"];
        $magic_number = sprintf("%x",$magic_number);

        // 根据magic判断字节序
        if ($magic_number === "a1b2c3d4") {
            // le
            $pcap->u32 = "V";
            $pcap->u16 = "v";
            $pcap->uC = "C";
        } else if ($magic_number === "d4c3b2a1") {
            // be, 网络字节序
            $pcap->u32 = "N";
            $pcap->u16 = "n";
            $pcap->uC = "C";
        } else {
            sys_abort("unsupport pcap magic");
        }

        /* Reading global header */
        $hdr_u16 = "v";
        $hdr_u32 = "V";
        $hdr = [
            $hdr_u16 . "version_major/",
            $hdr_u16 . "version_minor/",
            $hdr_u32 . "thiszone/",
            $hdr_u32 . "sigfigs/",
            $hdr_u32 . "snaplen/",
            $hdr_u32 . "network",
        ];
        $pcap_hdr = unpack(implode($hdr), $buffer->read(20));
        $pcap_hdr["magic_number"] = $magic_number;

        if ($pcap_hdr["network"] !== self::LINKTYPE_LINUX_SLL) {
            sys_abort("only support special Linux “cooked” capture");
        }

        // TCPDUMP 新版本 -s snaplen 最大值 已经不是65535了
        if ($pcap_hdr["snaplen"] !== 65535) {
            sys_abort("please set snaplen=0, tcpdump -s 0");
        }

        $self = new static;

        $self->magic_number = $pcap_hdr["magic_number"];
        $self->version_major = $pcap_hdr["version_major"];
        $self->version_minor = $pcap_hdr["version_minor"];
        $self->thiszone = $pcap_hdr["thiszone"];
        $self->sigfigs = $pcap_hdr["sigfigs"];
        $self->snaplen = $pcap_hdr["snaplen"];
        $self->network = $pcap_hdr["network"];

        return $self;
    }
}