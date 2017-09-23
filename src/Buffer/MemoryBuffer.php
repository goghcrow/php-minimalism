<?php

namespace Minimalism\Buffer;

use swoole_buffer as SwooleBuffer;


/**
 * Class Buffer
 *
 * 自动扩容, 从尾部写入数据，从头部读出数据
 * 参考
 *
 * +-------------------+------------------+------------------+
 * | prependable bytes |  readable bytes  |  writable bytes  |
 * |                   |     (CONTENT)    |                  |
 * +-------------------+------------------+------------------+
 * |                   |                  |                  |
 * V                   V                  V                  V
 * 0      <=      readerIndex   <=   writerIndex    <=     size
 *
 * @ref https://github.com/chenshuo/muduo/blob/master/muduo/net/Buffer.h
 */
class MemoryBuffer implements Buffer
{
    const kCheapPrepend = 8;
    const kInitialSize = 1024;

    protected $buffer;

    protected $readerIndex;

    protected $writerIndex;

    protected $evMap;

    public function __construct($size = self::kInitialSize)
    {
        $this->buffer = new SwooleBuffer($size + static::kCheapPrepend);
        $this->readerIndex = static::kCheapPrepend;
        $this->writerIndex = static::kCheapPrepend;
        $this->evMap = [];
    }

    public function getWriteIndex()
    {
        return $this->writerIndex;
    }

    public function setWriteIndex($index)
    {
        if ($index < $this->readerIndex || $index >= $this->buffer->capacity) {
            return false;
        }
        $this->writerIndex = $index;
        return true;
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
            $this->trigger("write", $bytes);
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
        $this->trigger("write", $bytes);
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

    public function on($ev, callable $cb)
    {
        $this->evMap[$ev] = $cb;
    }

    protected function trigger($ev, ...$args)
    {
        if (isset($this->evMap[$ev])) {
            $cb = $this->evMap[$ev];
            $cb(...$args);
        }
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