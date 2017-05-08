<?php

namespace Minimalism\PHPDump\Pcap;


use Minimalism\PHPDump\Buffer\Buffer;

/**
 * Class RecordHdr
 * Packet Header
 * @package Minimalism\PHPDump\Pcap
 *
 * typedef struct pcaprec_hdr_s {
 *      guint32 ts_sec;         // timestamp seconds
 *      guint32 ts_usec;        // timestamp microseconds
 *      guint32 incl_len;       // number of octets of packet saved in file
 *      guint32 orig_len;       // actual length of packet
 * } pcaprec_hdr_t;
 *
 */
class RecordHdr
{
    const SIZE = 16;

    const MAX_PACKET_SIZE = 1024 * 1024 * 2;

    public $ts_sec;
    public $ts_usec;
    public $incl_len;
    public $orig_len;

    public static function isReceiveCompleted(Buffer $buffer, Pcap $pcap)
    {
        $readableBytes = $buffer->readableBytes();
        if ($readableBytes < self::SIZE) {
            return false;
        }

        $hdr = $buffer->get(self::SIZE);

        $self = self::unpackFromString($hdr, $pcap);
        $incl_len = $self->incl_len;

        return $readableBytes >= ($incl_len + self::SIZE);
    }

    public static function unpack(Buffer $buffer, Pcap $pcap)
    {
        if ($buffer->readableBytes() < self::SIZE) {
            sys_abort("buffer is too small to read packet header");
        }

        $hdr = $buffer->read(self::SIZE);
        return self::unpackFromString($hdr, $pcap);
    }

    private static function unpackFromString($hdr, Pcap $pcap)
    {
        $rec_hdr_u32 = "V";
        $rec_hdr_fmt = [
            $rec_hdr_u32 . "ts_sec/",
            $rec_hdr_u32 . "ts_usec/",
            $rec_hdr_u32 . "incl_len/",
            $rec_hdr_u32 . "orig_len/",
        ];
        $rec_hdr = unpack(implode($rec_hdr_fmt), $hdr);

        $snaplen = $pcap->pcap_hdr->snaplen;
        $incl_len = $rec_hdr["incl_len"];
        $orig_len = $rec_hdr["orig_len"];

        if ($incl_len > static::MAX_PACKET_SIZE) {
            sys_abort("too large incl_len, $incl_len > " . static::MAX_PACKET_SIZE);
        }

        if ($incl_len > $orig_len || $incl_len > $snaplen || $incl_len === 0) {
            sys_abort("malformed read pcap record header");
        }

        $self = new static;

        $self->ts_sec = $rec_hdr["ts_sec"];
        $self->ts_usec = $rec_hdr["ts_usec"];
        $self->incl_len = $rec_hdr["incl_len"];
        $self->orig_len = $rec_hdr["orig_len"];

        return $self;
    }
}