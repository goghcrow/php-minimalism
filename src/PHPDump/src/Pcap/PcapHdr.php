<?php

namespace Minimalism\PHPDump\Pcap;


use Minimalism\PHPDump\Buffer\Buffer;

/**
 * Class PcapHdr
 * @package Minimalism\PHPDump\Pcap
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

        if ($pcap_hdr["network"] !== 113) {
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