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
use Minimalism\PHPDump\Pcap\Dissector;

class MySQLDissector implements Dissector
{
    private $state;

    private $serverStatus = 0; // TODO
    private $stmtNumFields = 0; // TODO

    private $isSLL;
    private $isCompressed;

    /**
     * @var int mysql port
     */
    private $mysqlServerPort;

    public function __construct($mysqlServerPort)
    {
        $this->mysqlServerPort = intval($mysqlServerPort);
        $this->state = MySQLState::UNDEFINED;
    }

    public function getName()
    {
        return "MySQL";
    }

    private function isResponse(Connection $connection)
    {
        if ($connection->TCPHdr->destination_port === $this->mysqlServerPort) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param Connection $connection
     * @return int DETECTED|UNDETECTED|DETECT_WAIT
     */
    public function detect(Connection $connection)
    {
        // TODO mysql 协议指纹？！
        // 抓取mysql需要人工过滤端口号 !!! 这里假定拿到的都是mysql协议流
        if ($connection->buffer->readableBytes() < 4) {
            return Dissector::DETECT_WAIT;
        } else {
            return Dissector::DETECTED;
        }
    }

    /**
     * @param Connection $connection
     * @return bool
     */
    public function isReceiveCompleted(Connection $connection)
    {
        $stream = new MySQLBinaryStream($connection->buffer);
        return $stream->isReceiveCompleted() > 0;
    }

    /**
     * @param Connection $connection
     * @return PDU|null
     */
    public function dissect(Connection $connection)
    {
        if ($connection->currentPacket === null) {
            $connection->currentPacket = new MySQLPDU();
        }

        $stream = new MySQLBinaryStream($connection->buffer);
        $packetLen = $stream->read3ByteIntLE();
        $packetNum = $stream->readUInt8();

        $isResponse = $this->isResponse($connection);
        if ($isResponse) {
            return $this->dissectResponse($packetLen, $packetNum, $stream, $connection);
        } else {
            return $this->dissectRequest($packetLen, $packetNum, $stream, $connection);
        }
    }

    private function dissectRequest($packetLen, $packetNum, MySQLBinaryStream $stream, Connection $connection)
    {
        /** @var MySQLPDU $packet */
        $packet = $connection->currentPacket;

        if ($this->state === MySQLState::LOGIN && $packetNum === 1) {
            // sys_echo("Authorization Request");

            $packet->pktType = MySQLPDU::PKT_AUTH;
            $packet->payload = $stream->readAuthorizationPacket();
            // TODO $packet->authInfo["capabilities"] 这里要根据客户端能力判断是否是压缩数据 !!!

            $this->state = MySQLState::RESPONSE_OK;
        } else {
            // sys_echo("Request");

            list($cmd, $args) = $stream->readCommand();
            $packet->pktType = MySQLPDU::PKT_CMD;
            $packet->payload = [$cmd, $args];

            switch ($cmd) {
                // TODO
                // COM_QUERY 不一定进入Response_tabular状态
                // if the query does not need to return a result set;
                // for example, INSERT, UPDATE, or ALTER TABLE

                case MySQLCommand::COM_PROCESS_INFO:
                case MySQLCommand::COM_QUERY:
                case MySQLCommand::COM_STMT_FETCH:
                case MySQLCommand::COM_STMT_EXECUTE:
                    $this->state = MySQLState::RESPONSE_TABULAR;
                    break;

                case MySQLCommand::COM_DEBUG:
                case MySQLCommand::COM_PING:

                case MySQLCommand::COM_INIT_DB:
                case MySQLCommand::COM_CREATE_DB:
                case MySQLCommand::COM_DROP_DB:

                case MySQLCommand::COM_STMT_RESET:
                case MySQLCommand::COM_PROCESS_KILL:
                case MySQLCommand::COM_CHANGE_USER:
                case MySQLCommand::COM_REFRESH:
                case MySQLCommand::COM_SHUTDOWN:
                case MySQLCommand::COM_SET_OPTION:
                    $this->state = MySQLState::RESPONSE_OK;
                    break;

                case MySQLCommand::COM_STATISTICS:
                    $this->state = MySQLState::RESPONSE_MESSAGE;
                    break;

                case MySQLCommand::COM_STMT_PREPARE:
                    $this->state = MySQLState::RESPONSE_PREPARE;
                    break;

                case MySQLCommand::COM_FIELD_LIST:
                    $this->state = MySQLState::RESPONSE_SHOW_FIELDS;
                    break;

                case MySQLCommand::COM_STMT_SEND_LONG_DATA:
                case MySQLCommand::COM_BINLOG_DUMP:
                case MySQLCommand::COM_TABLE_DUMP:
                case MySQLCommand::COM_CONNECT_OUT:
                case MySQLCommand::COM_REGISTER_SLAVE:
                case MySQLCommand::COM_STMT_CLOSE:
                    $this->state = MySQLState::REQUEST;
                    break;

                case MySQLCommand::COM_QUIT:
                    break;

                /*
                case MySQLCommand::COM_SLEEP:
                case MySQLCommand::COM_CONNECT:
                case MySQLCommand::COM_TIME:
                case MySQLCommand::COM_DELAYED_INSERT:
                */
                default:
                    $this->state = MySQLState::UNDEFINED;
            }
        }

        // 复用一个pdu对象 !!!
        // $connection->currentPacket = null;
        return $packet;
    }

    private function dissectResponse($packetLen, $packetNum, MySQLBinaryStream $stream, Connection $connection)
    {
        /** @var MySQLPDU $packet */
        $packet = $connection->currentPacket;

        if ($packetNum === 0 && $this->state === MySQLState::UNDEFINED) {
            // sys_echo("Server Greeting");

            $packet->pktType = MySQLPDU::PKT_GREETING;
            // $stream->readGreetingPacket(); // TODO
            $packet->payload = $stream->read($packetLen); // SKIP

            $this->state = MySQLState::LOGIN;

            $connection->currentPacket = null;
            return $packet;

        } else {

            $code = unpack("C", $stream->get(1))[1];
            $lenRemaining = $packetLen - 1;
            if ($code === 0xFF) { // ERR
                $this->state = MySQLState::REQUEST;

                // sys_echo("Server Response ERR");
                $packet->payload = $stream->readResponseERR($packetLen);
                // $packet->payload = $stream->read($packetLen); // SKIP
                $packet->pktType = MySQLPDU::PKT_ERR;
                $connection->currentPacket = null;
                return $packet;

            } else if ($code === 0x00) { // OK
                // sys_echo("Server Response");
                // OK OR ResultSetHeaderFieldCount

                if ($this->state === MySQLState::RESPONSE_PREPARE) {
                    $this->state = MySQLState::REQUEST;

                    assert(false); // proxy 不支持prepare语句, 所以这里应该不会走到
                    // sys_echo("Server Response Prepare OK");
                    // $packet->okPkt = $stream->readPrepareOK(); // TODO 这里必须读出来
                    $packet->payload = $stream->read($packetLen); // SKIP
                    $packet->pktType = MySQLPDU::PKT_STMT_OK;
//                        if (stmt_num_params > 0) {
//                            $this->state = MySQLState::PREPARED_PARAMETERS;
//                        } else if (stmt_num_fields > 0) {
//                            $this->state = MySQLState::PREPARED_FIELDS;
//                        } else {
//                        }
                    $connection->currentPacket = null;
                    return $packet;

                } else if ($lenRemaining > $stream->getLengthCodedBinaryLen()) {
                    $this->state = MySQLState::REQUEST;

                    // sys_echo("Server Response OK");
                    $packet->payload = $stream->readResponseOK($packetLen);
                    // $packet->payload = $stream->read($packetLen); // SKIP
                    $packet->pktType = MySQLPDU::PKT_OK;
                    $connection->currentPacket = null;
                    return $packet;

                } else {
                    list($fieldCount, $ext) = $stream->readResultSetHeader($packetLen);
                    $packet->pktType = MySQLPDU::PKT_RESULT;
                    $packet->payload = ["header" => [$fieldCount, $ext], "fields" => [], "rows" => []];
                }

            } else if ($code === 0xFE && $lenRemaining < 9) { // EOF

                // if ($lenRemaining > 0) {
                    // 4.1 协议都应该有
                    assert($lenRemaining > 0);
                    list($this->serverStatus, $flags) = $stream->readEOF();
                    // sys_echo("Server Response EOF, serverStatus=0x" . dechex($this->serverStatus) . ", flags=0x" . dechex($flags));
                // }

                switch ($this->state) {
                    case MySQLState::FIELD_PACKET:
                        // 读完field声明 之后应该读数据
                        $this->state = MySQLState::ROW_PACKET;
                        break;

                    case MySQLState::ROW_PACKET:
                        // more result
                        if ($this->serverStatus & 0x0008) {// #define MYSQL_STAT_MU 0x0008
                            $this->state = MySQLState::RESPONSE_TABULAR;
                        } else {
                            $this->state = MySQLState::REQUEST;

                            $connection->currentPacket = null;
                            return $packet;
                        }
                        break;

                    case MySQLState::PREPARED_PARAMETERS:
                        // parameter -> field
                        if ($this->stmtNumFields > 0) {
                            $this->state = MySQLState::PREPARED_FIELDS;
                        } else {
                            $this->state = MySQLState::REQUEST;

                            $connection->currentPacket = null;
                            return $packet;
                        }
                        break;

                    case MySQLState::PREPARED_FIELDS:
                        $this->state = MySQLState::REQUEST;
                        break;

                    default:
                        // 这里应该抓到请求响应不完整的包，比如直接抓到field字段
                        $this->state = MySQLState::REQUEST;
                        return $packet;
                }
            } else {

                switch ($this->state) {
                    case MySQLState::RESPONSE_MESSAGE:
                        $this->state = MySQLState::REQUEST;

                        // sys_echo("Server Response Message");
                        $stream->read($packetLen); // SKIP
                        break;

                    case MySQLState::REQUEST: // TODO WHY !
                    case MySQLState::RESPONSE_TABULAR:
                        list($fieldCount, $ext) = $stream->readResultSetHeader($packetLen);
                        $packet->pktType = MySQLPDU::PKT_RESULT;
                        $packet->payload = ["header" => [$fieldCount, $ext], "fields" => [], "rows" => []];
                        // sys_echo("Server Response Tabular, fieldCount=$fieldCount, ext=$ext");
                        if ($fieldCount > 0) {
                            $this->state = MySQLState::FIELD_PACKET;
                        } else {
                            $this->state = MySQLState::ROW_PACKET;
                        }
                        break;

                    case MySQLState::FIELD_PACKET:
                    case MySQLState::RESPONSE_SHOW_FIELDS:
                    case MySQLState::RESPONSE_PREPARE:
                    case MySQLState::PREPARED_PARAMETERS:
                        // sys_echo("Server Response Field");
                        $packet->payload["fields"][] = $stream->readField($packetLen);
                        break;

                    case MySQLState::ROW_PACKET:
                        // sys_echo("Server Response Row " . implode(", ", $row));
                        $packet->payload["rows"][] = $stream->readRowData($packetLen);
                        break;

                    case MySQLState::PREPARED_FIELDS:
                        // sys_echo("Server Response Field");
                        $packet->payload["fields"][] = $stream->readField($packetLen);
                        break;

                    case MySQLState::AUTH_SWITCH_REQUEST:
                        $stream->read($packetLen); // SKIP
                        break;

                    default:
                        $stream->read($packetLen); // SKIP
                        $this->state = MySQLState::UNDEFINED;
                }

            }
        }

        return null;
    }
}