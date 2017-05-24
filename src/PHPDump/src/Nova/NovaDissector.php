<?php

namespace Minimalism\PHPDump\Nova;


use Minimalism\PHPDump\Buffer\Buffer;
use Minimalism\PHPDump\Pcap\Connection;
use Minimalism\PHPDump\Pcap\Dissector;


/**
 * Class NovaProtocol
 * @package Minimalism\PHPDump\Nova
 *
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
class NovaDissector implements Dissector
{
    // const SIZE = nova_header_size;
    const MAX_PACKET_SIZE = 1024 * 1024 * 2;
    const MSG_SIZE_LEN = 4;
    const NOVA_HEADER_COMMON_LEN = 37;

    public function getName()
    {
        return "Nova";
    }

    public function detect(Connection $connection)
    {
        $buffer = $connection->buffer;

        $hl = self::NOVA_HEADER_COMMON_LEN;
        if ($buffer->readableBytes() >= $hl) {
            if (is_nova_packet($buffer->get($hl))) {
                return Dissector::DETECTED;
            } else {
                return Dissector::UNDETECTED;
            }
        } else {
            return Dissector::DETECT_WAIT;
        }
    }

    public function isReceiveCompleted(Connection $connection)
    {
        $connBuffer = $connection->buffer;

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

    public function dissect(Connection $connection)
    {
        $connBuffer = $connection->buffer;

        if ($connBuffer->readableBytes() < self::MSG_SIZE_LEN) {
            sys_abort("buffer is too small to read nova msg size");
        }

        $msg_size = unpack("Nmsg_size", $connBuffer->get(self::MSG_SIZE_LEN))["msg_size"];
        if ($connBuffer->readableBytes() < $msg_size) {
            sys_abort("buffer is too small to read nova msg");
        }

        $nova_data = $connBuffer->read($msg_size);

        $packet = new NovaPDU();

        // if ($msg_size < nova_header_size)
        // is_nova_packet($data)
        $ok = nova_decode($nova_data,
            $packet->service,
            $packet->method,
            $packet->ip,
            $packet->port,
            $packet->seq,
            $packet->attach,
            $packet->thriftBin);

        if (!$ok) {
            sys_abort("nova_decode fail, hex: " . bin2hex($nova_data));
        }
        $packet->ip = long2ip($packet->ip);
        $packet->dstIp = $connection->IPHdr->destination_ip;
        $packet->dstPort = $connection->TCPHdr->destination_port;

        return $packet;
    }
}