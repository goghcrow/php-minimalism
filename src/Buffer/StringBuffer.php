<?php

namespace Minimalism\Buffer;


class StringBuffer implements Buffer
{
    private $bytes = "";

    public static function ofBytes($bytes)
    {
        $self = new static;
        $self->bytes = $bytes;
        return $self;
    }

    public function __destruct()
    {
        unset($this->bytes);
    }

    public function __clone()
    {
        $this->bytes = "";
    }

    public function __toString()
    {
        return $this->bytes;
    }

    public function write($bytes)
    {
        $this->bytes .= $bytes;
    }

    public function read($len)
    {
        $ret = substr($this->bytes, 0, $len);
        $this->bytes = substr($this->bytes, $len);
        return $ret;
    }

    public function readFull()
    {
        return $this->bytes;
    }

    public function reset()
    {
        $this->bytes = "";
    }
}