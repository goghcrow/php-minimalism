<?php

namespace Minimalism\PHPDump\Http;


class HttpCopy
{
    public $file;

    public function __construct($file)
    {
        $this->file = $file;
    }

    public function __invoke(HttpPDU $httpPacket)
    {
        $curl = $httpPacket->asCurl();
        swoole_async_write($this->file, $curl, -1);
    }
}