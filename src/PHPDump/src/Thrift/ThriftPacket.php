<?php

namespace Minimalism\PHPDump\Thrift;


use Minimalism\PHPDump\Buffer\BufferFactory;

class ThriftPacket
{
    public $thriftBin;

    public $type;
    public $seqId;
    public $name;
    public $fields;

    public static function unpack($thriftBin)
    {
        $buffer = BufferFactory::make();
        $buffer->write($thriftBin);
        $tCodec = new TCodec($buffer);

        $self = $tCodec->decode();
        $self->thriftBin = $thriftBin;
        return $self;
    }
}