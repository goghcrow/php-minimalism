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
use Minimalism\PHPDump\Util\AsciiTable;
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

    public $pktNums = [];
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

        if (count($this->pktNums) > 1) {
            $s = reset($this->pktNums);
            $e = end($this->pktNums);
            $pktNum = T::format("$s~$e", T::BRIGHT);
        } else {
            $pktNum = T::format($this->pktNums[0], T::BRIGHT);
        }

        $sec = $connection->recordHdr->ts_sec;
        $usec = $connection->recordHdr->ts_usec;

        switch ($this->pktType) {

            case static::PKT_CMD:
                list($cmd, $args) = $this->payload;
                if ($cmd === MySQLCommand::COM_QUERY) {
                    sys_echo("$src > $dst pktnum $pktNum", $sec, $usec);
                    $sql = T::format($args["sql"], T::FG_GREEN);
                    sys_echo($sql, $sec, $usec);
                }
                break;

            case static::PKT_RESULT:

                $sql = "";
                if ($reverseConnection = $connection->reverseConnection) {
                    if (property_exists($reverseConnection, "requestPacket")) {
                        /** @noinspection PhpUndefinedFieldInspection */
                        $packet = $reverseConnection->requestPacket;
                        list($cmd, $args) = $packet->payload;
                        if ($cmd === MySQLCommand::COM_QUERY) {
                            $sql = $args["sql"];
                        }
                    }
                }

                $res = $this->payload;
                list($fieldCount, $ext) = $res["header"];

                list($fields, $fieldsStr, $rows) = $this->formatResult($res["fields"], $res["rows"]);
                sys_echo("$src > $dst  pktnum $pktNum", $sec, $usec);

                if ($sql) {
                    echo T::format("($sql)", T::DIM), "\n";
                } else {

                    // TODO TODO debug
                    if ($reverseConnection = $connection->reverseConnection) {
                        if (property_exists($reverseConnection, "requestPacket")) {
                            /** @noinspection PhpUndefinedFieldInspection */
                            $packet = $reverseConnection->requestPacket;
                            list($cmd, $args) = $packet->payload;
                            if ($cmd === MySQLCommand::COM_QUERY) {
                                $sql = $args["sql"];
                            } else {
                                sys_error("DEBUG $cmd cmd类型不对");
                            }
                        } else {
                            sys_error("DEBUG 没有属性");
                            // var_dump($reverseConnection->buffer->get(PHP_INT_MAX));
                        }
                    } else {
                        sys_error("DEBUG 没有反向连接");
                    }
                }

                // echo T::format(implode("\n", $fieldsStr), T::DIM), "\n";
                (new AsciiTable())->draw($fields, 10, 50);
                (new AsciiTable())->draw($rows);
                echo T::format(json_encode($rows), T::DIM), "\n";
                echo "\n";
                break;


            case static::PKT_OK:
            case static::PKT_ERR:
                $r = T::format(json_encode($this->payload), T::DIM);
                sys_echo("$src > $dst $r", $sec, $usec);
                break;

            default:

        }
    }

    /**
     * @param MySQLField[] $fields
     * @param array $rows
     * @return array
     */
    private function formatResult(array $fields, array $rows)
    {
        $names = array_column($fields, "name");
        $fmtRows = [];
        foreach ($rows as $row) {
            foreach ($fields as $i => $field) {
                $row[$i] = $field->fmtValue($row[$i]);
            }
            $fmtRows[] = array_combine($names, $row);
        }

        $fmtFieldsStr = [];
        $fmtFields = [];

        foreach ($fields as $field) {
            $fmtFieldsStr[] = $field->__toString();
            $fmtFields[] = $field->__debugInfo();
        }

        return [$fmtFields, $fmtFieldsStr, $fmtRows];
    }
}