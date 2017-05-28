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
    // request
    const PKT_AUTH = 2;
    const PKT_CMD = 3;

    // response
    const PKT_GREETING = 1;
    const PKT_OK = 4;
    const PKT_ERR = 5;
    const PKT_STMT_OK = 6;
    const PKT_RESULT = 7;

    public $pktType;
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

        switch ($this->pktType) {
            case static::PKT_CMD:
                list($cmd, $args) = $this->payload;
                if ($cmd === MySQLCommand::COM_QUERY) {
                    echo $args["sql"], "\n";
                }
                break;
            case static::PKT_RESULT:
                $res = $this->payload;
                list($fieldCount, $ext) = $res["header"];
                $r = $this->formatResult($res["fields"], $res["rows"]);
                echo json_encode($r), "\n\n";
                break;

            case static::PKT_OK:
                // TODO affected rows |lasted insert id
                // TODO pkt
                echo "OK\n\n";
                break;

            case static::PKT_ERR:
                // TODO pkt
                echo "ERR\n\n";
                break;

            default:

        }
    }

    private function formatResult(array $fields, array $rows)
    {
        $names = array_column($fields, "name");
        $r = [];
        foreach ($rows as $row) {
            $r[] = array_combine($names, $row);
        }
        return $r;
    }
}