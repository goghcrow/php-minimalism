<?php

namespace Minimalism\FakeServer\Buffer;


class StringBuffer implements Buffer
{
    private $bytes = "";

    public function __construct($bytes = "")
    {
        $this->bytes = $bytes;
    }

    public function write($bytes)
    {
        $this->bytes .= $bytes;
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

    public function __toString()
    {
        return $this->bytes;
    }
}