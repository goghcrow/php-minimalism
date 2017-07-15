<?php

namespace Minimalism\Buffer;


class StringBuffer implements Buffer
{
    private $bytes = "";

    protected $evMap;

    public function __construct($bytes = "")
    {
        $this->bytes = $bytes;
        $this->evMap = [];
    }

    public function write($bytes)
    {
        $this->bytes .= $bytes;
        $this->trigger("write", $bytes);
        return true;
    }

    public function prepend($bytes)
    {
        $this->bytes = $bytes . $this->bytes;
        return true;
    }

    public function get($len)
    {
        return substr($this->bytes, 0, $len);
    }

    public function read($len)
    {
        $t = substr($this->bytes, 0, $len);
        $this->bytes = substr($this->bytes, $len);
        return $t;
    }

    public function readFull()
    {
        return $this->bytes;
    }

    public function readableBytes()
    {
        return strlen($this->bytes);
    }

    public function writableBytes()
    {
        return PHP_INT_MAX;
    }

    public function prependableBytes()
    {
        return PHP_INT_MAX;
    }

    public function search($str, $offset = 0)
    {
        return strpos($this->bytes, $str, $offset);
    }

    public function readUntil($sep, $included = false)
    {
        $pos = strpos($this->bytes, $sep);
        if ($pos === false) {
            return false;
        } else {
            if ($included) {
                $offset = $pos + strlen($sep);
                $r = substr($this->bytes, 0, $offset);;
                $this->bytes = substr($this->bytes, $offset);
                return $r;
            } else {
                $r = substr($this->bytes, 0, $pos);
                $this->bytes = substr($this->bytes, $pos + strlen($sep));
                return $r;
            }
        }
    }

    public function getUntil($sep, $included = false)
    {
        $pos = strpos($this->bytes, $sep);
        if ($pos === false) {
            return false;
        } else {
            return substr($this->bytes, 0, $pos + ($included ? strlen($sep) : 0));
        }
    }

    public function readLine($br = "\r\n", $included = false)
    {
        return $this->readUntil($br, $included);
    }

    public function getLine($br = "\r\n", $included = false)
    {
        return $this->getUntil($br, $included);
    }

    public function peek($offset, $len = 1)
    {
        return substr($this->bytes, $offset, $len);
    }

    public function reset()
    {
        $this->bytes = "";
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

    public function __toString()
    {
        return $this->bytes;
    }

    public function __debugInfo()
    {
        if (strlen($this->bytes)) {
            $hex = implode(" ", array_map(function($v) { return "0x$v"; }, str_split(bin2hex($this->__toString()), 2)));
        }else {
            $hex = "";
        }
        return [
            "string" => $this->bytes,
            "hex" => $hex,
            "readableBytes" => $this->readableBytes(),
        ];
    }
}