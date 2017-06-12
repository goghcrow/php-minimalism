<?php

namespace __;



//$msg = "*2\r\n:4\r\n*4\r\n\$4\r\nhoge\r\n\$6\r\nfoobar\r\n\$3\r\nfoo\r\n\$10\r\nhelloworld\r\n";
//$conn = new Connection();
//$conn->buffer->write($msg);
//$conn->loopDissect();
//exit;

//$msg = "*2\r\n:4\r\n*4\r\n\$4\r\nhoge\r\n\$6\r\nfoobar\r\n\$3\r\nfoo\r\n\$11\r\nhelloworld!\r\n";
//$conn = new Connection();
//$chars = str_split(strrev($msg), 1);
//while (($char = array_pop($chars)) !== null) {
//    $conn->buffer->write($char);
//    $conn->loopDissect();
//}
//exit;






//$msg = "*2\r\n:4\r\n*3\r\n\$4\r\nhoge\r\n\$6\r\nfoobar\r\n*2\r\n+OK\r\n-ERR\r\n";
//$conn = new Connection();
//$conn->buffer->write($msg);
//$conn->loopDissect();
//exit;


//$msg = "*2\r\n:4\r\n*3\r\n\$4\r\nhoge\r\n\$6\r\nfoobar\r\n*2\r\n+OK\r\n-ERR\r\n";
//$conn = new Connection();
//$chars = str_split(strrev($msg), 1);
//while (($char = array_pop($chars)) !== null) {
//    $conn->buffer->write($char);
//    $conn->loopDissect();
//}
//exit;



$msg = "*2\r\n:4\r\n*3\r\n\$4\r\nhoge\r\n\$6\r\nfoobar\r\n*4\r\n*-1\r\n$-1\r\n+OK\r\n-ERR\r\n";
var_dump(strlen($msg));
$buf = new MemoryBuffer();

$buf->write($msg);
$r = parse($buf);
print_r($r);


$chars = str_split(strrev($msg), 1);
while (($char = array_pop($chars)) !== null) {
    $buf->write($char);
    var_dump(isRecvCompleted($buf));
}

function isRecvCompleted(MemoryBuffer $buf, $offset = 0)
{
    if ($buf->search("\r\n", $offset) === false) {
        return false;
    }

    $type = $buf->peek($offset, 1);
    $offset += 1;
    switch ($type) {
        case '*':
            $a = $offset;
            $offset = $buf->search("\r\n", $offset);
            if ($offset === false) {
                return false;
            } else {
                $ll = $offset - $a;
                $l = intval($buf->peek($a, $ll));
                $offset += 2;
                $l = max(0, $l);
                for ($i = 0; $i < $l; $i++) {
                    $offset = isRecvCompleted($buf, $offset);
                    if ($offset === false) {
                        return false;
                    }
                }
                return $offset;
            }

        case '$':
            $a = $offset;
            $offset = $buf->search("\r\n", $offset);
            if ($offset === false) {
                return false;
            } else {
                $ll = $offset - $a;
                $l = intval($buf->peek($a, $ll));
                $offset += 2;
                if ($l <= 0) {
                    return $offset;
                } else {
                    $offset = $offset + $l + 2;
                    $has = $buf->readableBytes();
                    if ($has >= $offset) {
                        return $offset;
                    } else {
                        return false;
                    }
                }
            }
            break;

        case ':':
        case '+':
        case '-':
            $offset = $buf->search("\r\n", $offset);
            if ($offset === false) {
                return false;
            } else {
                return $offset + 2;
            }

        default:
            exit("ERROR");
    }
}

function parse(MemoryBuffer $buf)
{
    $msg = new RedisPDU();
    $type = $buf->read(1);
    $msg->msgType = $type;

    switch ($type) {
        case '*':
            $l = intval($buf->readLine());
            if ($l > 0) {
                $msg->payload = [];
                for ($i = 0; $i < $l; $i++) {
                    $msg->payload[] = parse($buf);
                }
            } else {
                $msg->payload = $l;
            }
            break;

        case '$':
            $l = intval($buf->readLine()); // -1
            if ($l > 0) {
                $msg->payload = $buf->read($l);
                $crlf = $buf->read(2);
                assert($crlf === "\r\n");
            } else {
                $msg->payload = $l;
            }
            break;

        case ':':
            $msg->payload = intval($buf->readLine());
            break;
        case '+':
        case '-':
            $msg->payload = $buf->readLine();
            break;
    }

    return $msg;
}

class Connection
{
    public $buffer;
    public $state;
    public $currentPacket;
    public $dissector;

    public function __construct()
    {
        $this->buffer = new MemoryBuffer();
        $this->dissector = new RedisDissector();
        $this->state = RedisDissector::STATE_INIT;
        $this->isPrepend = false;
    }


    public function loopDissect()
    {
        while (true) {
            if ($this->dissector->isReceiveCompleted($this)) {
                $pdu = $this->dissector->dissect($this);
                if ($pdu) {
                    print_r($pdu->payload);
                }
            } else {
                break;
            }
        }
    }
}


class RedisPDU
{
    const MSG_STATUS = '+';
    const MSG_ERROR = '-';
    const MSG_INTEGER = ':';
    const MSG_BULK = '$';
    const MSG_MULTI = '*';


    public $__cur;
    public $__n;

    public $parent;
    public $msgType;
    public $payload;

    public function __toString()
    {
        return json_encode($this->payload);
    }
}


class RedisDissector
{
    const STATE_INIT = 0;
    const STATE_HALF = 1;
    const STATE_HALF2 = 2;

    public function isReceiveCompleted(Connection $connection)
    {
        if ($connection->isPrepend) {
            $index = $connection->buffer->search("\r\n");
            assert($index !== false);
            $index = $connection->buffer->search("\r\n", $index + 2);
        } else {
            $index = $connection->buffer->search("\r\n");
        }

        if ($index) {
            $connection->isPrepend = false;
        }
        return $index !== false;
    }

    private function setState($state, Connection $connection)
    {
        $connection->state = $state;
    }

    public function dissect(Connection $connection)
    {
        switch ($connection->state) {
            case static::STATE_INIT:
                list($msg, $done) = $this->dissectMessage($connection);
                if ($done) {
                    { // done
                        $this->setState(static::STATE_INIT, $connection);
                        $connection->currentPacket = null;
                    }
                    return $msg;
                } else {
                    $connection->currentPacket = $msg;
                    return null;
                }

            case static::STATE_HALF2: // Multi-Bulk
                /** @var $msg \Minimalism\PHPDump\Redis\RedisPDU */
                $msg = $connection->currentPacket;

                switch ($msg->msgType) {
                    case RedisPDU::MSG_MULTI:
                        if ($msg->__cur !== null) {

                        }
//                        if ($msg->__cur === null) {
                            assert($msg->__cur === null);
                            for ($i = 0; $i < $msg->__n; $i++) {
                                /** @var $subMsg RedisPDU */
                                list($subMsg, $done) = $this->dissectMessage($connection);
                                if ($done) {
                                    $subMsg->parent = $msg;
                                    $msg->payload[] = $subMsg;
                                } else {
                                    $msg->__cur = $subMsg;
                                    if ($subMsg === null) {
                                        $this->setState(static::STATE_HALF2, $connection);
                                    } else {
                                        $this->setState(static::STATE_HALF, $connection);
                                    }
                                    return null;
                                }
                            }

                            { // done
                                $this->setState(static::STATE_INIT, $connection);
                                $connection->currentPacket = null;
                            }
                            return $msg;
//                        } else {
//                            $curMsg = $msg;
//                            while ($curMsg->__cur !== null) {
//                                $curMsg->__cur->parent = $curMsg;
//                                $curMsg = $curMsg->__cur;
//                            }
//
//                            assert($curMsg->__cur === null);
//
//                            while ($curMsg->__cur === null) {
//                                for ($i = 0; $i < $curMsg->__n; $i++) {
//                                    /** @var $subMsg RedisPDU */
//                                    list($subMsg, $done) = $this->dissectMessage($connection);
//                                    if ($done) {
//                                        $subMsg->parent = $curMsg;
//                                        $curMsg->payload[] = $subMsg;
//                                    } else {
//                                        $curMsg->__cur = $subMsg;
//                                        if ($subMsg === null) {
//                                            $this->setState(static::STATE_HALF2, $connection);
//                                        } else {
//                                            $this->setState(static::STATE_HALF, $connection);
//                                        }
//                                        return null;
//                                    }
//                                }
//
////                                $curMsg->parent->payload[] = $curMsg;
//                                if ($curMsg->parent === null) {
//                                    break;
//                                }
//
//                                $curMsg = $curMsg->parent;
//                            }
//
//                            { // done
//                                $this->setState(static::STATE_INIT, $connection);
//                                $connection->currentPacket = null;
//                            }
//                            return $msg;
//                        }



                    default:
                        sys_abort("invalid parser state3: " . $connection->buffer->readFull());
                        return null; // for ide
                }

            case static::STATE_HALF: // Multi-Bulk
                /** @var $msg \Minimalism\PHPDump\Redis\RedisPDU */
                $msg = $connection->currentPacket;

                switch ($msg->msgType) {
                    case RedisPDU::MSG_MULTI:
                        assert($msg->__cur !== null);
                        $curMsg = $msg->__cur;
                        while ($curMsg->__cur) {
                            $curMsg = $curMsg->__cur;
                        }


                        while ($curMsg) {
                            $n = $curMsg->__n;
                            $left = $n - count($curMsg->payload);
                            for ($i = 0; $i < $left; $i++) {
                                /** @var $subMsg RedisPDU */
                                list($subMsg, $done) = $this->dissectMessage($connection);
                                if ($done) {
                                    $subMsg->parent = $curMsg;
                                    $curMsg->payload[] = $subMsg;
                                } else {
                                    $curMsg->__n = $n;
                                    $curMsg->__cur = $subMsg;
                                    return null;
                                }
                            }

                            if ($curMsg->parent === null) {
                                $msg->payload[] = $curMsg;
                            }

                            $curMsg = $curMsg->parent;
                        }

                        { // done
                            $this->setState(static::STATE_INIT, $connection);
                            $connection->currentPacket = null;
                        }
                        return $msg;

                    default:
                        sys_abort("invalid parser state1: " . $connection->buffer->readFull());
                        return null; // for ide
                }

            default:
                sys_abort("invalid parser state2: " . $connection->buffer->readFull());
                return null; // for ide
        }
    }

    private function dissectMessage(Connection $connection)
    {
        $buf = $connection->buffer;

        if ($buf->search("\r\n") === false) {
            return [null, false];
        }

        $msg = new RedisPDU();
        $msgType = $buf->read(1);
        $msg->msgType = $msgType;

        switch ($msgType) {
            case RedisPDU::MSG_STATUS:
                $msg->payload = $buf->readLine();
                return [$msg, true];

            case RedisPDU::MSG_ERROR:
                $msg->payload = $buf->readLine();
                return [$msg, true];

            case RedisPDU::MSG_INTEGER:
                $msg->payload = intval($buf->readLine());
                return [$msg, true];

            case RedisPDU::MSG_BULK:
                $l = intval($buf->readLine());
                if ($l < 0) {
                    $msg->payload = $l;
                    return [$msg, true];
                } else {
                    if ($buf->readableBytes() < $l) {
                        $buf->prepend("{$msgType}{$l}\r\n"); // return to init
                        $connection->isPrepend = true; // !!!
                        return [null, false]; // !!! 因为prepend回去所以返回null
                    } else {
                        $msg->payload = $buf->read($l);
                        $crlf = $buf->read(2);
                        assert($crlf === "\r\n");
                        return [$msg, true];
                    }
                }

            case RedisPDU::MSG_MULTI:
                $n = intval($buf->readLine());
                if ($n < 0) {
                    $msg->payload = $n;
                    return [$msg, true];
                } else {
                    $msg->payload = [];
                    for ($i = 0; $i < $n; $i++) {
                        /** @var $subMsg RedisPDU */
                        list($subMsg, $done) = $this->dissectMessage($connection);
                        if ($done) {
                            $subMsg->parent = $msg;
                            $msg->payload[] = $subMsg;
                        } else {
                            $msg->__n = $n;
                            $msg->__cur = $subMsg;
                            if ($subMsg === null) {
                                $this->setState(static::STATE_HALF2, $connection);
                            } else {
                                $this->setState(static::STATE_HALF, $connection);
                            }
                            return [$msg, false];
                        }
                    }
                    return [$msg, true];
                }

            default:
                $left = $buf->readFull();
                sys_abort("Invalid Redis Line: {$msgType}{$left}");
                return [null, null]; // for ide
        }
    }
}


















class MemoryBuffer
{
    const kCheapPrepend = 8;
    const kInitialSize = 1024;

    protected $buffer;

    protected $readerIndex;

    protected $writerIndex;

    public function __construct($size = self::kInitialSize)
    {
        $this->buffer = new \swoole_buffer($size + static::kCheapPrepend);
        $this->readerIndex = static::kCheapPrepend;
        $this->writerIndex = static::kCheapPrepend;

        /*
        assert(static::readableBytes() === 0);
        assert(static::writableBytes() === static::kInitialSize);
        assert(static::prependableBytes() === static::kCheapPrepend);
        */
    }

    public function readableBytes()
    {
        return $this->writerIndex - $this->readerIndex;
    }

    public function writableBytes()
    {
        return $this->buffer->capacity - $this->writerIndex;
    }

    public function prependableBytes()
    {
        return $this->readerIndex;
    }

    public function capacity()
    {
        return $this->buffer->capacity;
    }

    public function get($len)
    {
        if ($len <= 0) {
            return "";
        }

        $len = min($len, $this->readableBytes());
        return $this->rawRead($this->readerIndex, $len);
    }

    public function read($len)
    {
        if ($len <= 0) {
            return "";
        }

        $len = min($len, $this->readableBytes());
        $read = $this->rawRead($this->readerIndex, $len);
        $this->readerIndex += $len;
        if ($this->readerIndex === $this->writerIndex) {
            $this->reset();
        }
        return $read;
    }

    public function readFull()
    {
        return $this->read($this->readableBytes());
    }

    public function write($bytes)
    {
        if ($bytes === "") {
            return false;
        }

        $len = strlen($bytes);

        if ($len <= $this->writableBytes()) {
            $this->rawWrite($this->writerIndex, $bytes);
            $this->writerIndex += $len;
            return true;
        }

        // expand
        if ($len > ($this->prependableBytes() + $this->writableBytes() - static::kCheapPrepend)) {
            $this->expand(($this->writerIndex + $len) * 2);
        }

        // copy-move 内部腾挪
        if ($this->readerIndex !== static::kCheapPrepend) {
            $this->rawWrite(static::kCheapPrepend, $this->rawRead($this->readerIndex, $this->writerIndex - $this->readerIndex));
            $this->writerIndex = $this->writerIndex - $this->readerIndex + static::kCheapPrepend;
            $this->readerIndex = static::kCheapPrepend;
        }

        $this->rawWrite($this->writerIndex, $bytes);
        $this->writerIndex += $len;
        return true;
    }

    public function prepend($bytes)
    {
        if ($bytes === "") {
            return false;
        }

        $size = $this->prependableBytes();
        $len = strlen($bytes);
        if ($len > $size) {
            throw new \InvalidArgumentException("no space to prepend [len=$len, size=$size]");
        }
        $this->rawWrite($size - $len, $bytes);
        $this->readerIndex -= $len;
        return true;
    }

    public function search($str, $offset = 0)
    {
        $len = strlen($str);
        $offset = $this->readerIndex + $offset;
        $end = $this->writerIndex - $len;

        while($offset <= $end) {
            if ($str === $this->buffer->read($offset, $len)) {
                return $offset - $this->readerIndex;
            }
            $offset++;
        }

        return false;
    }

    public function findCRLF()
    {
        return $this->search("\r\n");
    }

    public function getUntil($sep, $included = false)
    {
        $offset = $this->search($sep);

        if ($offset === false) {
            return false;
        } else {
            if ($included) {
                return $this->get($offset + strlen($sep));
            } else {
                return $this->get($offset);
            }
        }
    }

    public function readUntil($sep, $included = false)
    {
        $offset = $this->search($sep);

        if ($offset === false) {
            return false;
        } else {
            if ($included) {
                return $this->read($offset + strlen($sep));
            } else {
                $r = $this->read($offset);
                $this->read(strlen($sep));
                return $r;
            }
        }
    }

    public function getLine($br = "\r\n", $included = false)
    {
        return $this->getUntil($br, $included);
    }

    public function readLine($br = "\r\n", $included = false)
    {
        return $this->readUntil($br, $included);
    }

    public function peek($offset, $len = 1)
    {
        $offset = $this->readerIndex + max(0, $offset);
        $len = min($len, $this->writerIndex - $offset);
        return $this->rawRead($offset, $len);
    }

    public function reset()
    {
        $this->readerIndex = static::kCheapPrepend;
        $this->writerIndex = static::kCheapPrepend;
    }

    private function rawRead($offset, $len)
    {
        if ($len === 0) {
            return "";
        }
        if ($offset < 0 || $offset + $len > $this->buffer->capacity) {
            throw new \InvalidArgumentException(__METHOD__ . ": offset=$offset, len=$len, capacity={$this->buffer->capacity}");
        }
        return $this->buffer->read($offset, $len);
    }

    private function rawWrite($offset, $bytes)
    {
        if ($bytes === "") {
            return 0;
        }
        $len = strlen($bytes);
        if ($offset < 0 || $offset + $len > $this->buffer->capacity) {
            throw new \InvalidArgumentException(__METHOD__ . ": offset=$offset, len=$len, capacity={$this->buffer->capacity}");
        }
        return $this->buffer->write($offset, $bytes);
    }

    private function expand($size)
    {
        if ($size <= $this->buffer->capacity) {
            throw new \InvalidArgumentException(__METHOD__ . ": size=$size, capacity={$this->buffer->capacity}");
        }
        return $this->buffer->expand($size);
    }

    public function __toString()
    {
        return $this->rawRead($this->readerIndex, $this->writerIndex - $this->readerIndex);
    }

    public function __debugInfo()
    {
        $str = $this->__toString();
        if (strlen($str)) {
            $hex = implode(" ", array_map(function($v) { return "0x$v"; }, str_split(bin2hex($str), 2)));
        }else {
            $hex = "";
        }
        return [
            "string" => $str,
            "hex" => $hex,
            "capacity" => $this->capacity(),
            "readerIndex" => $this->readerIndex,
            "writerIndex" => $this->writerIndex,
            "prependableBytes" => $this->prependableBytes(),
            "readableBytes" => $this->readableBytes(),
            "writableBytes" => $this->writableBytes(),
        ];
    }
}