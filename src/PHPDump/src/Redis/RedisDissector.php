<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/5/31
 * Time: 下午3:23
 */

namespace Minimalism\PHPDump\Redis;


use Minimalism\PHPDump\Pcap\Connection;
use Minimalism\PHPDump\Pcap\Dissector;
use Minimalism\PHPDump\Pcap\PDU;

/**
 * Class RedisDissector
 * @package Minimalism\PHPDump\Redis
 *
 * @ref https://redis.io/topics/protocol
 * @ref http://redisdoc.com/topic/protocol.html
 */
class RedisDissector implements Dissector
{
    /*
    private $redisServerPort;

    public function __construct($redisServerPort = null)
    {
        $this->redisServerPort = $redisServerPort;
    }

    private function isRequest(Connection $connection)
    {
        assert($this->redisServerPort !== null);

        if ($connection->TCPHdr->destination_port === $this->redisServerPort) {
            return true;
        } else {
            return false;
        }
    }
    */

    public function getName()
    {
        return "Redis";
    }

    /**
     * @param Connection $connection
     * @return int DETECTED|UNDETECTED|DETECT_WAIT
     */
    public function detect(Connection $connection)
    {
        $buf = $connection->buffer;

        if ($buf->readableBytes() < 4) {
            return Dissector::DETECT_WAIT;
        } else {
            $in = strpos("+-:$*", $buf->get(1));
            $index = $buf->search("\r\n");
            if ($in && $index) {
                return Dissector::DETECTED;
            } else {
                return Dissector::UNDETECTED;
            }
        }
    }

    /**
     * @param Connection $connection
     * @return bool
     */
    public function isReceiveCompleted(Connection $connection)
    {
        $msgLen = $this->isReceiveCompletedRecursive($connection);
        return $msgLen;
    }


    /**
     * @param Connection $connection
     * @return PDU|null
     */
    public function dissect(Connection $connection)
    {
        /*
        $isRequest = $this->isRequest($connection);
        if ($isRequest) {
            return $this->dissectRequest();
        } else {
            return $this->dissectResponse();
        }
        */
        return $this->dissectRecursive($connection);
    }

    private function isReceiveCompletedRecursive(Connection $connection, $offset = 0)
    {
        $buf = $connection->buffer;

        if ($buf->search("\r\n", $offset) === false) {
            return false;
        }

        $type = $buf->peek($offset, 1);
        $offset += 1;
        switch ($type) {
            case RedisPDU::MSG_ARRAY:
                $a = $offset;
                $offset = $buf->search("\r\n", $offset);
                if ($offset === false) {
                    return false;
                } else {
                    $ll = $offset - $a;
                    $l = filter_var($buf->peek($a, $ll), FILTER_VALIDATE_INT);
                    assert($l !== false, "buf: " . $buf->get(PHP_INT_MAX));
                    $offset += 2;
                    if ($l === -1) {
                        return $offset;
                    } else if ($l === 0) {
                        return $offset;
                    } else if ($l > 0) {
                        for ($i = 0; $i < $l; $i++) {
                            $offset = $this->isReceiveCompletedRecursive($connection, $offset);
                            if ($offset === false) {
                                return false;
                            }
                        }
                        return $offset;
                    } else {
                        sys_error("invalid MSG_ARRAY len: $l");
                        return false; // for ide
                    }
                }

            case RedisPDU::MSG_BULK_STRING:
                $a = $offset;
                $offset = $buf->search("\r\n", $offset);
                if ($offset === false) {
                    return false;
                } else {
                    $ll = $offset - $a;
                    $l = filter_var($buf->peek($a, $ll), FILTER_VALIDATE_INT);
                    assert($l !== false, "buf: " . $buf->get(PHP_INT_MAX));
                    $offset += 2;
                    if ($l === -1) { // nil $-1\r\n
                        return $offset;
                    } else if ($l === 0) { // empty string $0\r\n\r\n
                        return $offset + 2;
                    } else if ($l > 0) {
                        $offset = $offset + $l + 2;
                        $has = $buf->readableBytes();
                        if ($has >= $offset) {
                            return $offset;
                        } else {
                            return false;
                        }
                    } else {
                        sys_error("invalid MSG_BULK len: $l");
                        return false; // for ide
                    }
                }
                break;

            case RedisPDU::MSG_INTEGER:
            case RedisPDU::MSG_STRING:
            case RedisPDU::MSG_ERROR:
                $offset = $buf->search("\r\n", $offset);
                if ($offset === false) {
                    return false;
                } else {
                    return $offset + 2;
                }

            default:
                sys_abort("invalid redis msg(1): type=$type, raw=" . $connection->buffer->readFull());
                return false; // for ide
        }
    }

    private function dissectRecursive(Connection $connection)
    {
        $buf = $connection->buffer;

        $msg = new RedisPDU();
        $msg->msgType = $buf->read(1);

        switch ($msg->msgType) {
            case RedisPDU::MSG_ARRAY:
                $l = filter_var($buf->readLine(), FILTER_VALIDATE_INT);
                assert($l !== false, "buf: " . $buf->get(PHP_INT_MAX));
                if ($l === -1) { // nil $-1\r\n
                    $msg->payload = "nil";
                } else if ($l === 0) {
                    $msg->payload = [];
                } else if ($l > 0) {
                    $msg->payload = [];
                    for ($i = 0; $i < $l; $i++) {
                        $msg->payload[] = $this->dissectRecursive($connection);
                    }
                } else {
                    sys_error("invalid MSG_MULTI len: $l");
                }
                break;

            case RedisPDU::MSG_BULK_STRING:
                $l = filter_var($buf->readLine(), FILTER_VALIDATE_INT);
                assert($l !== false, "buf: " . $buf->get(PHP_INT_MAX));
                if ($l === -1) {
                    $msg->payload = "nil";
                } else if ($l === 0) { // empty string $0\r\n\r\n
                    $msg->payload = "";
                    $crlf = $buf->read(2);
                    assert($crlf === "\r\n", "buf: " . $buf->get(PHP_INT_MAX));
                } else if ($l > 0) {
                    $msg->payload = $buf->read($l);
                    $crlf = $buf->read(2);
                    assert($crlf === "\r\n", "buf: " . $buf->get(PHP_INT_MAX));
                } else {
                    sys_error("invalid MSG_BULK len: $l");
                }
                break;

            case RedisPDU::MSG_INTEGER:
                $msg->payload = filter_var($buf->readLine(), FILTER_VALIDATE_INT);
                assert($msg->payload !== false, "buf: " . $buf->get(PHP_INT_MAX));
                break;
            case RedisPDU::MSG_STRING:
            case RedisPDU::MSG_ERROR:
                $msg->payload = $buf->readLine();
                break;

            default:
                sys_abort("invalid redis msg(2): type={$msg->msgType}, raw=" . $connection->buffer->readFull());
        }

        return $msg;
    }
}