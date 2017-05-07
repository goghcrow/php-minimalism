<?php

namespace Minimalism\PHPDump\Thrift;


use Minimalism\PHPDump\Buffer\Buffer;

class TBinaryReader
{
    const VERSION_1 = 0x80010000;

    private $buffer;

    public function __construct(Buffer $buffer)
    {
        $this->buffer = $buffer;
    }

    public function readMessageBegin()
    {
        $sz = $this->readI32();
        assert($sz < 0); // 只处VERSION_1, 高位字节第一bit恒等于1(leading bit is 1)
        $version = (int) ($sz & 0xffff0000); // 高位两字节版本
        // 这里我们使用协议 VERSION_1 0x80010000 // 低位第二字节未使用
        assert($version === (int) self::VERSION_1);

        $type = $sz & 0x000000ff;  // 低位一字节消息类型
        $name = $this->readString();
        $seqId = $this->readI32();

        return [$type, $name, $seqId];
    }

    public function readMessageEnd() { }

    public function readStructBegin() { }
    public function readStructEnd() { }

    public function readFieldBegin()
    {
        $fieldType = $this->readByte();
        if ($fieldType == TType::STOP) {
            $fieldId = 0;
        } else {
            $fieldId = $this->readI16();
        }

        return [$fieldType, $fieldId];
    }

    public function readFieldEnd() { }

    public function readMapBegin()
    {
        $keyType = $this->readByte();
        $valType = $this->readByte();
        $size = $this->readI32();
        return [$keyType, $valType, $size];
    }

    public function readMapEnd() { }

    public function readListBegin()
    {
        $elemType = $this->readByte();
        $size = $this->readI32();
        return [$elemType, $size];
    }

    public function readListEnd() { }

    public function readSetBegin()
    {
        $elemType = $this->readByte();
        $size = $this->readI32();
        return [$elemType, $size];
    }

    public function readSetEnd() { }

    public function readBool()
    {
        $data = $this->buffer->read(1);
        return (unpack('c', $data)[1]) == 1;
    }

    public function readByte()
    {
        $data = $this->buffer->read(1);
        return unpack('c', $data)[1];
    }

    public function readI16()
    {
        $data = $this->buffer->read(2);
        $arr = unpack('n', $data);
        $value = $arr[1];
        if ($value > 0x7fff) {
            $value = 0 - (($value - 1) ^ 0xffff);
        }
        return $value;
    }

    public function readI32()
    {
        $data = $this->buffer->read(4);
        $arr = unpack('N', $data);
        $value = $arr[1];
        if ($value > 0x7fffffff) { // TODO why
            $value = 0 - (($value - 1) ^ 0xffffffff);
        }
        return $value;
    }

    public function readI64()
    {
        $data = $this->buffer->read(8);
        $arr = unpack('N2', $data);

        assert(PHP_INT_SIZE === 8);

        // Upcast negatives in LSB bit
        if ($arr[2] & 0x80000000) {
            $arr[2] = $arr[2] & 0xffffffff;
        }

        // Check for a negative
        if ($arr[1] & 0x80000000) {
            $arr[1] = $arr[1] & 0xffffffff;
            $arr[1] = $arr[1] ^ 0xffffffff;
            $arr[2] = $arr[2] ^ 0xffffffff;
            return 0 - $arr[1]*4294967296 - $arr[2] - 1;
        } else {
            return $arr[1]*4294967296 + $arr[2];
        }
    }

    public function readDouble()
    {
        $data = strrev($this->buffer->read(8)); // TODO WHY reverse ?
        return unpack('d', $data)[1];
    }

    public function readString()
    {
        $len = $this->readI32();
        if ($len > 0) {
            return $this->buffer->read($len);
        } else {
            return "";
        }
    }
}