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
    private $serverStatus = 0;  // TODO
    private $stmtNumFields = 0; // TODO
    private $isSLL;             // TODO
    private $isCompressed;      // TODO

    /**
     * @var int mysql port
     */
    private $mysqlServerPort;

    public function __construct($mysqlServerPort)
    {
        $this->mysqlServerPort = intval($mysqlServerPort);
    }

    public function getName()
    {
        return "MySQL";
    }

    // 状态记录在requestConnection中
    private function setState($state, Connection $requestConnection)
    {
        $requestConnection->state = $state;

        if ($state === MySQLState::REQUEST) {
            /** @noinspection PhpUndefinedFieldInspection */
            $requestConnection->stateTrace = [];
        }

        // for debug
        /** @noinspection PhpUndefinedFieldInspection */
        $requestConnection->stateTrace[] = MySQLState::getName($state);
    }

    private function connectionInit(Connection $connection)
    {
        $isRequest = $this->isRequest($connection);
        if ($isRequest) {
            // 状态保存在 request connection
            /** @noinspection PhpUndefinedFieldInspection */
            $connection->state = MySQLState::UNDEFINED;
            /** @noinspection PhpUndefinedFieldInspection */
            $connection->stateTrace = [];
        } else {
            //
            /** @noinspection PhpUndefinedFieldInspection */
            $connection->requestPacket = null;
        }
    }

    private function isRequest(Connection $connection)
    {
        if ($connection->TCPHdr->destination_port === $this->mysqlServerPort) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param Connection $connection
     * @return int DETECTED|UNDETECTED|DETECT_WAIT
     */
    public function detect(Connection $connection)
    {
        // 不容易识别mysql协议指纹？！抓取mysql需要人工过滤端口号 !!! 这里假定拿到的都是mysql协议流
        if ($connection->buffer->readableBytes() < 4) {
            return Dissector::DETECT_WAIT;
        } else {
            $this->connectionInit($connection);
            return Dissector::DETECTED;
        }
    }

    /**
     * @param Connection $connection
     * @return bool
     */
    public function isReceiveCompleted(Connection $connection)
    {
        $buffer = $connection->buffer;

        if ($buffer->readableBytes() < 4) {
            return false;
        } else {
            $bin = $buffer->get(4);
            $len = unpack("V", substr($bin, 0, 3) . "\0\0")[1];

            if ($len <= 0 || $len >= 1024 * 1024 * 16) {
                sys_error("malformed mysql packet, len=$len");
                $connection->close();
                return false;
            }

            return $buffer->readableBytes() >= $len + 4;
        }
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

        // 记录packet num
        $connection->currentPacket->pktNums[] = $packetNum;
        $isRequest = $this->isRequest($connection);
        if ($isRequest) {
            return $this->dissectRequest($packetLen, $packetNum, $stream, $connection);
        } else {
            if ($reverseConnection = $connection->reverseConnection) {
                return $this->dissectResponse($packetLen, $packetNum, $stream, $connection, $reverseConnection);
            } else {
                $stream->read($packetLen);
                return null;
            }
        }
    }

    private function dissectRequest($packetLen, $packetNum, MySQLBinaryStream $stream, Connection $connection)
    {
        /** @var MySQLPDU $packet */
        $packet = $connection->currentPacket;

        if ($connection->state === MySQLState::LOGIN && $packetNum === 1) {
            // sys_echo("Authorization Request");

            $packet->pktType = MySQLPDU::PKT_AUTH;
            $packet->payload = $stream->readAuthorizationPacket();
            // TODO $packet->authInfo["capabilities"] 这里要根据客户端能力判断是否是压缩数据 !!!

            $this->setState(MySQLState::RESPONSE_OK, $connection);
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

                case MySQLCommand::COM_QUERY:
                case MySQLCommand::COM_PROCESS_INFO:
                case MySQLCommand::COM_STMT_FETCH:
                case MySQLCommand::COM_STMT_EXECUTE:
                    $this->setState(MySQLState::RESPONSE_TABULAR, $connection);
                    break;

                case MySQLCommand::COM_DEBUG:
                case MySQLCommand::COM_PING:

                case MySQLCommand::COM_INIT_DB:
                case MySQLCommand::COM_CREATE_DB:
                case MySQLCommand::COM_DROP_DB:

                case MySQLCommand::COM_SET_OPTION:
                case MySQLCommand::COM_STMT_RESET:
                case MySQLCommand::COM_PROCESS_KILL:
                case MySQLCommand::COM_CHANGE_USER:
                case MySQLCommand::COM_REFRESH:
                case MySQLCommand::COM_SHUTDOWN:
                    $this->setState(MySQLState::RESPONSE_OK, $connection);
                    break;

                case MySQLCommand::COM_STATISTICS:
                    $this->setState(MySQLState::RESPONSE_MESSAGE, $connection);
                    break;

                case MySQLCommand::COM_STMT_PREPARE:
                    $this->setState(MySQLState::RESPONSE_PREPARE, $connection);
                    break;

                case MySQLCommand::COM_FIELD_LIST:
                    $this->setState(MySQLState::RESPONSE_SHOW_FIELDS, $connection);
                    break;

                case MySQLCommand::COM_STMT_SEND_LONG_DATA:
                case MySQLCommand::COM_BINLOG_DUMP:
                case MySQLCommand::COM_TABLE_DUMP:
                case MySQLCommand::COM_CONNECT_OUT:
                case MySQLCommand::COM_REGISTER_SLAVE:
                case MySQLCommand::COM_STMT_CLOSE:
                    $this->setState(MySQLState::REQUEST, $connection);
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
                    $this->setState(MySQLState::UNDEFINED, $connection);
            }
        }

        $connection->currentPacket = null;

        /** @noinspection PhpUndefinedFieldInspection */
        $connection->requestPacket = $packet;

        return $packet;
    }

    private function dissectResponse($packetLen, $packetNum, MySQLBinaryStream $stream,
                                     Connection $connection, Connection $requestConnection)
    {
        /** @var MySQLPDU $packet */
        $packet = $connection->currentPacket;

        if ($packetNum === 0 && $requestConnection->state === MySQLState::UNDEFINED) {
            // sys_echo("Server Greeting");

            $packet->pktType = MySQLPDU::PKT_GREETING;
            // $stream->readGreetingPacket(); // TODO
            $packet->payload = $stream->read($packetLen); // SKIP

            $this->setState(MySQLState::LOGIN, $requestConnection);

            $connection->currentPacket = null;
            return $packet;

        } else {

            $code = unpack("C", $stream->get(1))[1];
            $lenRemaining = $packetLen - 1;
            if ($code === 0xFF) { // ERR
                $this->setState(MySQLState::REQUEST, $requestConnection);

                // sys_echo("Server Response ERR");
                $packet->payload = $stream->readResponseERR($packetLen);
                $packet->pktType = MySQLPDU::PKT_ERR;
                $connection->currentPacket = null;
                return $packet;

            } else if ($code === 0x00) { // OK
                // sys_echo("Server Response");
                // OK OR ResultSetHeaderFieldCount

                if ($requestConnection->state === MySQLState::RESPONSE_PREPARE) {
                    $this->setState(MySQLState::REQUEST, $requestConnection);

                    assert(false); // proxy 不支持prepare语句, 所以这里应该不会走到
                    // sys_echo("Server Response Prepare OK");
                    // $packet->okPkt = $stream->readPrepareOK(); // TODO 这里必须读出来
                    $packet->payload = $stream->read($packetLen); // SKIP
                    $packet->pktType = MySQLPDU::PKT_STMT_OK;
//                        if (stmt_num_params > 0) {
//                          $this->setState(MySQLState::PREPARED_PARAMETERS, $requestConnection);
//                        } else if (stmt_num_fields > 0) {
//                          $this->setState(MySQLState::PREPARED_FIELDS, $requestConnection);
//                        } else {
//                        }
                    $connection->currentPacket = null;
                    return $packet;

                } else if ($lenRemaining > $stream->getLengthCodedBinaryLen()) {
                    $this->setState(MySQLState::REQUEST, $requestConnection);

                    // sys_echo("Server Response OK");
                    $packet->payload = $stream->readResponseOK($packetLen);
                    $packet->pktType = MySQLPDU::PKT_OK;
                    $connection->currentPacket = null;
                    return $packet;

                } else {
                    list($fieldCount, $ext) = $stream->readResultSetHeader($packetLen);
                    $packet->pktType = MySQLPDU::PKT_RESULT;
                    $packet->payload = ["header" => [$fieldCount, $ext], "fields" => [], "rows" => []];
                }

            } else if ($code === 0xFE && $lenRemaining < 9) { // EOF

                // 4.1 协议都应该有
                if ($lenRemaining > 0) {
                    /** @noinspection PhpUnusedLocalVariableInspection */
                    list($this->serverStatus, $flags) = $stream->readEOF();
                    // sys_echo("Server Response EOF, serverStatus=0x" . dechex($this->serverStatus) . ", flags=0x" . dechex($flags));
                }

                switch ($requestConnection->state) {
                    case MySQLState::FIELD_PACKET:
                        // 读完field声明 之后应该读数据
                        $this->setState(MySQLState::ROW_PACKET, $requestConnection);
                        break;

                    case MySQLState::ROW_PACKET:
                        // more result
                        if ($this->serverStatus & 0x0008) {// #define MYSQL_STAT_MU 0x0008
                            $this->setState(MySQLState::RESPONSE_TABULAR, $requestConnection);
                        } else {
                            $this->setState(MySQLState::REQUEST, $requestConnection);

                            $connection->currentPacket = null;
                            return $packet;
                        }
                        break;

                    case MySQLState::PREPARED_PARAMETERS:
                        // parameter -> field
                        if ($this->stmtNumFields > 0) {
                            $this->setState(MySQLState::PREPARED_FIELDS, $requestConnection);
                        } else {
                            $this->setState(MySQLState::REQUEST, $requestConnection);

                            $connection->currentPacket = null;
                            return $packet;
                        }
                        break;

                    case MySQLState::PREPARED_FIELDS:
                        $this->setState(MySQLState::REQUEST, $requestConnection);
                        break;

                    default:
                        // 这里应该抓到请求响应不完整的包，比如直接抓到field字段
                        $this->setState(MySQLState::REQUEST, $requestConnection);
                        return $packet;
                }
            } else {

                switch ($requestConnection->state) {
                    case MySQLState::RESPONSE_MESSAGE:
                        $this->setState(MySQLState::REQUEST, $requestConnection);

                        // sys_echo("Server Response Message");
                        $stream->read($packetLen); // SKIP
                        break;

                    case MySQLState::RESPONSE_TABULAR:
                        list($fieldCount, $ext) = $stream->readResultSetHeader($packetLen);
                        $packet->pktType = MySQLPDU::PKT_RESULT;
                        $packet->payload = ["header" => [$fieldCount, $ext], "fields" => [], "rows" => []];
                        // sys_echo("Server Response Tabular, fieldCount=$fieldCount, ext=$ext");
                        if ($fieldCount > 0) {
                            $this->setState(MySQLState::FIELD_PACKET, $requestConnection);
                        } else {
                            $this->setState(MySQLState::ROW_PACKET, $requestConnection);
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
                        $this->setState(MySQLState::UNDEFINED, $requestConnection);
                }

            }
        }

        return null;
    }
}