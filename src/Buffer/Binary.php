<?php

namespace Minimalism\Buffer;



class Binary implements Buffer
{
    private $buffer;

    public function __construct(Buffer $buffer = null)
    {
        if ($buffer === null) {
            $this->buffer = new MemoryBuffer();
        } else {
            $this->buffer = $buffer;
        }
    }

    public function __destruct()
    {
        if (is_callable([$this->buffer, "__destruct"])) {
            /** @noinspection PhpUndefinedMethodInspection */
            $this->buffer->__destruct();
        }
    }

    public function __clone()
    {
        $this->buffer = clone $this->buffer;
        $this->buffer->reset();
    }

    public function writeUInt8($i)
    {
        return $this->buffer->write(pack('C', $i));
    }

    public function writeUInt16BE($i)
    {
        return $this->buffer->write(pack('n', $i));
    }

    public function writeUInt16LE($i)
    {
        return $this->buffer->write(pack('v', $i));
    }

    public function writeUInt32BE($i)
    {
        return $this->buffer->write(pack('N', $i));
    }

    public function writeUInt32LE($i)
    {
        return $this->buffer->write(pack('V', $i));
    }

    public function writeUInt64BE($uint64Str)
    {
        $low = bcmod($uint64Str, "4294967296");
        $hi = bcdiv($uint64Str, "4294967296", 0);
        return $this->buffer->write(pack("NN", $hi, $low));
    }

    public function writeUInt64LE($uint64Str)
    {
        $low = bcmod($uint64Str, "4294967296");
        $hi = bcdiv($uint64Str, "4294967296", 0);
        return $this->buffer->write(pack('VV', $low, $hi));
    }

    public function writeInt32BE($i)
    {
        return $this->buffer->write(pack('N', $i));
    }

    public function writeInt32LE($i)
    {
        return $this->buffer->write(pack('V', $i));
    }

    public function writeFloat($f)
    {
        return $this->buffer->write(pack('f', $f));
    }

    public function writeDouble($d)
    {
        return $this->buffer->write(pack('d', $d));
    }

    public function readUInt8()
    {
        $ret = unpack("Cr", $this->buffer->read(1));
        return $ret == false ? null : $ret["r"];
    }

    public function readUInt16BE()
    {
        $ret = unpack("nr", $this->buffer->read(2));
        return $ret === false ? null : $ret["r"];
    }

    public function readUInt16LE()
    {
        $ret = unpack("vr", $this->buffer->read(2));
        return $ret === false ? null : $ret["r"];
    }

    public function readUInt32BE()
    {
        $ret = unpack("nhi/nlo", $this->buffer->read(4));
        return $ret === false ? null : (($ret["hi"] << 16) | $ret["lo"]);
    }

    public function readUInt32LE()
    {
        $ret = unpack("vlo/vhi", $this->buffer->read(4));
        return $ret === false ? null : (($ret["hi"] << 16) | $ret["lo"]);
    }

    public function readUInt64BE()
    {
        $param = unpack("Nhi/Nlow", $this->buffer->read(8));
        return bcadd(bcmul($param["hi"], "4294967296", 0), $param["low"]);
    }

    public function readUInt64LE()
    {
        $param = unpack("Vlow/Vhi", $this->buffer->read(8));
        return bcadd(bcmul($param["hi"], "4294967296", 0), $param["low"]);
    }

    public function readInt32BE()
    {
        $ret = unpack("Nr", $this->buffer->read(4));
        return $ret === false ? null : $ret["r"];
    }

    public function readInt32LE()
    {
        $ret = unpack("Vr", $this->buffer->read(4));
        return $ret === false ? null : $ret["r"];
    }

    public function readFloat()
    {
        $ret = unpack("fr", $this->buffer->read(4));
        return $ret === false ? null : $ret["r"];
    }

    public function readDouble()
    {
        $ret = unpack("dr", $this->buffer->read(8));
        return $ret === false ? null : $ret["r"];
    }

    public function write($bytes)
    {
        return $this->buffer->write($bytes);
    }

    public function read($len)
    {
        return $this->buffer->read($len);
    }

    public function readFull()
    {
        return $this->buffer->readFull();
    }

    public function reset()
    {
        return $this->buffer->reset();
    }
}