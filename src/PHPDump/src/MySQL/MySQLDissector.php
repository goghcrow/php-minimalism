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

    public function getName()
    {
        return "MySQL";
    }

    public function __construct($mysqlServerPort)
    {
        $this->mysqlServerPort = intval($mysqlServerPort);
        $this->state = MySQLState::UNDEFINED;
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
     * @return PDU
     */
    public function dissect(Connection $connection)
    {
        $isResponse = $this->isResponse($connection);

        $stream = new MySQLBinaryStream($connection->buffer);
        $packetLen = $stream->read3ByteIntLE();
        $packetNum = $stream->readUInt8();

        if ($isResponse) {
            return $this->dissectResponse($packetLen, $packetNum, $stream);
        } else {
            return $this->dissectRequest($packetLen, $packetNum, $stream);
        }
    }

    private function dissectRequest($packetLen, $packetNum, MySQLBinaryStream $stream)
    {
        $packet = new MySQLPDU();

        if ($this->state === MySQLState::LOGIN && $packetNum === 1) {
            sys_echo("Authorization Request");
            $p = $stream->readAuthorizationPacket();
            // TODO $p["capabilities"] 这里要根据客户端能力判断是否是压缩数据 !!!
            print_r($p);

            $this->state = MySQLState::RESPONSE_OK;
        } else {
            list($cmd, $args) = $stream->readCommand();
            sys_echo("Request $cmd");
            print_r($args);

            switch ($cmd) {
                // COM_QUERY 不一定进入Response_tabular状态  TODO
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


                case MySQLCommand::COM_SLEEP:
                case MySQLCommand::COM_QUIT:
                case MySQLCommand::COM_CONNECT:
                case MySQLCommand::COM_TIME:
                case MySQLCommand::COM_DELAYED_INSERT:
                default:
                    $this->state = MySQLState::UNDEFINED;
            }
        }

        return $packet;
    }

    private function dissectResponse($packetLen, $packetNum, MySQLBinaryStream $stream)
    {
        $packet = new MySQLPDU();

        if ($packetNum === 0 && $this->state === MySQLState::UNDEFINED) {

            sys_echo("Server Greeting");
            // $stream->readGreetingPacket(); // TODO
            $stream->read($packetLen); // SKIP
            $this->state = MySQLState::LOGIN;

        } else {

            $code = unpack("C", $stream->get(1))[1];
            $lenRemaining = $packetLen - 1;
            if ($code === 0xFF) { // ERR

                sys_echo("Server Response ERR");
                // $stream->readResponseERR($len); // TODO
                $stream->read($packetLen); // SKIP
                $this->state = MySQLState::REQUEST;

            } else if ($code === 0x00) { // OK
                sys_echo("Server Response");
                // OK OR ResultSetHeaderFieldCount
                if ($this->state === MySQLState::RESPONSE_PREPARE) {
                    assert(false); // proxy 不支持prepare语句, 所以这里应该不会走到
                    sys_echo("Server Response Prepare OK");
                    // $stream->readPrepareOK(); // TODO 这里必须读出来
                    $stream->read($packetLen); // SKIP
//                        if (stmt_num_params > 0) {
//                            $this->state = MySQLState::PREPARED_PARAMETERS;
//                        } else if (stmt_num_fields > 0) {
//                            $this->state = MySQLState::PREPARED_FIELDS;
//                        } else {
                    $this->state = MySQLState::REQUEST;
//                        }
                } else if ($lenRemaining > $stream->getLengthCodedBinaryLen()) {

                    sys_echo("Server Response OK");
                    // $stream->readResponseOK($len); // TODO
                    $stream->read($packetLen); // SKIP
                    $this->state = MySQLState::REQUEST;

                } else {
                    var_dump($this->state);
                    list($fieldCount, $ext) = $stream->readResultSetHeader($packetLen);
                    sys_echo("Server Response ResultSetHeader, fieldCount=$fieldCount, ext=$ext");
                }

            } else if ($code === 0xFE && $lenRemaining < 9) { // EOF

                // 4.1 协议都应该有
                assert($lenRemaining > 0);
//                if ($lenRemaining > 0) {
                    list($this->serverStatus, $flags) = $stream->readEOF();
                    $status = dechex($this->serverStatus);
                    $flags = dechex($flags);
                    sys_echo("Server Response EOF, serverStatus=0x$status, flags=0x$flags");
//                }

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
                        }
                        break;

                    case MySQLState::PREPARED_PARAMETERS:
                        // parameter -> field
                        if ($this->stmtNumFields > 0) {
                            $this->state = MySQLState::PREPARED_FIELDS;
                        } else {
                            $this->state = MySQLState::REQUEST;
                        }
                        break;

                    case MySQLState::PREPARED_FIELDS:
                        $this->state = MySQLState::REQUEST;
                        break;

                    default:
                        // 这里应该抓到请求响应不完整的包，比如直接抓到field字段
                        $this->state = MySQLState::REQUEST;
                }
            } else {

                switch ($this->state) {
                    case MySQLState::RESPONSE_MESSAGE:
                        sys_echo("Server Response Message");
                        $stream->read($packetLen); // SKIP
                        $this->state = MySQLState::REQUEST;
                        break;

                    case MySQLState::REQUEST: // TODO WHY 感觉是有地方的状态不应该标记成REQUEST
                    case MySQLState::RESPONSE_TABULAR:
//                        RESPONSE_TABULAR:
                        list($fieldCount, $ext) = $stream->readResultSetHeader($packetLen);
                        sys_echo("Server Response Tabular, fieldCount=$fieldCount, ext=$ext");
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
//                            $r = $stream->read($len);
//                            echo $r, "\n";echo bin2hex($r), "\n";
                        $field = $stream->readField($packetLen);
                        sys_echo("Server Response Field");
                        print_r($field);
                        break;

                    case MySQLState::ROW_PACKET:
                        $row = $stream->readRowData($packetLen);
                        sys_echo("Server Response Row " . implode(", ", $row));
                        break;

                    case MySQLState::PREPARED_FIELDS:
                        sys_echo("Server Response Field");
                        $field = $stream->readField($packetLen);
                        print_r($field);
                        break;

                    case MySQLState::AUTH_SWITCH_REQUEST:
                        $stream->read($packetLen); // SKIP
                        break;


                    default:
                        // TODO
//                        goto RESPONSE_TABULAR;
//                        // why state = request
                        echo "===================================\n";
                        var_dump($this->state);
                        var_dump($packetLen);
                        echo $stream->read($packetLen), "\n"; // SKIP
                        $this->state = MySQLState::UNDEFINED;
                }

            }
        }

        return $packet;
    }
}