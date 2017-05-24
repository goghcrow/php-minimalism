<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/5/22
 * Time: 下午11:45
 */

namespace Minimalism\PHPDump\MySQL;


use Minimalism\PHPDump\Pcap\Connection;
use Minimalism\PHPDump\Pcap\PDU;
use Minimalism\PHPDump\Util\T;

class MySQLPDU extends PDU
{
    public $packetType;
    public $cmdType;
    public $payload;

    /**
     * @param Connection $connection
     */
    public function inspect(Connection $connection)
    {
        $srcIp = $connection->IPHdr->source_ip;
        $dstIp = $connection->IPHdr->destination_ip;
        $srcPort = $connection->TCPHdr->source_port;
        $dstPort = $connection->TCPHdr->destination_port;

        $src = T::format("$srcIp:$srcPort", T::BRIGHT);
        $dst = T::format("$dstIp:$dstPort", T::BRIGHT);

        $sec = $connection->recordHdr->ts_sec;
        $usec = $connection->recordHdr->ts_usec;


        if (/*$this->packetType === MySQLBinaryStream::PACKET_TYPE_CMD
            && */$this->cmdType === MySQLCommand::COM_QUERY) {
            $sql = $this->payload;

            echo $sql, "\n";
        }
    }
}