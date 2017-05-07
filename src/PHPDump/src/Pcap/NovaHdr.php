<?php

namespace Minimalism\PHPDump\Pcap;


use Minimalism\PHPDump\Buffer\Buffer;

/**
 * Class NovaHdr
 * @package Minimalism\PHPDump\Pcap
 *
 * #define NOVA_MAGIC              0xdabc
 * #define NOVA_HEADER_COMMON_LEN  37
 *
 * Header
 *
 * typedef struct swNova_Header{
 *      int32_t     msg_size; // contains body
 *      uint16_t    magic;
 *      int16_t     head_size;
 *      int8_t      version;
 *      uint32_t    ip;
 *      uint32_t    port;
 *      int32_t     service_len;
 *      char        *service_name;
 *      int32_t     method_len;
 *      char        *method_name;
 *      int64_t     seq_no; // req id <-> res id
 *      int32_t     attach_len;
 *      char        *attach;
 *  }swNova_Header;
 *
 * Body
 *
 * message body thrift serialize
 */
class NovaHdr
{
    // const SIZE = nova_header_size;
    const MAX_PACKET_SIZE = 1024 * 1024 * 2;
    const MSG_SIZE_LEN = 4;
    const NOVA_HEADER_COMMON_LEN = 37;

    public $service;
    public $method;
    public $ip;
    public $port;
    public $seq;
    public $attach;
    public $thriftBin;

    public static function detect(Buffer $recordBuffer)
    {
        $hl = self::NOVA_HEADER_COMMON_LEN;
        return $recordBuffer->readableBytes() >= $hl && is_nova_packet($recordBuffer->get($hl));
    }

    public static function isReceiveCompleted(Buffer $connBuffer, Pcap $pcap)
    {
        // 4byte nova msg_size
        if ($connBuffer->readableBytes() < self::MSG_SIZE_LEN) {
            return false;
        }

        $msg_size = unpack("Nmsg_size", $connBuffer->get(self::MSG_SIZE_LEN))["msg_size"];
        if ($msg_size > static::MAX_PACKET_SIZE) {
            sys_abort("capture too large nova packet, $msg_size > 1024 * 1024 * 2");
        }
        if ($connBuffer->readableBytes() < $msg_size) {
            return false;
        }

        return true;
    }

    public static function unpack(Buffer $connBuffer, Pcap $pcap)
    {
        if ($connBuffer->readableBytes() < self::MSG_SIZE_LEN) {
            sys_abort("buffer is too small to read nova msg size");
        }

        $msg_size = unpack("Nmsg_size", $connBuffer->get(self::MSG_SIZE_LEN))["msg_size"];
        if ($connBuffer->readableBytes() < $msg_size) {
            sys_abort("buffer is too small to read nova msg");
        }

        $nova_data = $connBuffer->read($msg_size);

        $self = new static;

        // if ($msg_size < nova_header_size)
        // is_nova_packet($data)
        $ok = nova_decode($nova_data,
            $self->service,
            $self->method,
            $self->ip,
            $self->port,
            $self->seq,
            $self->attach,
            $self->thriftBin);

        if ($ok) {
            sys_abort("nova_decode fail, hex: " . bin2hex($nova_data));
        }
        $self->ip = long2ip($self->ip);

        return $self;
    }
}