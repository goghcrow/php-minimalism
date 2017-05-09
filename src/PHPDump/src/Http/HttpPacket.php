<?php

namespace Minimalism\PHPDump\Http;


use Minimalism\PHPDump\Pcap\Connection;
use Minimalism\PHPDump\Pcap\Packet;


/**
 * Class HttpPacket
 * @package Minimalism\PHPDump\Http
 */
class HttpPacket extends Packet
{
    const REQUEST = 1;
    const RESPONSE = 2;
    public $type;

    public $httpVer;

    public $requestLine;
    public $method;
    public $uri;

    public $statusLine;
    public $statusCode;
    public $reasonPhrase;

    public $header;
    public $chunkExt;
    public $body;


    const STATE_UN_FIN = 1;
    const STATE_HEADER_FIN = 2;
    const STATE_BODY_FIN = 3;
    const STATE_ERROR = 4;
    public $state = self::STATE_UN_FIN;

    public $isHttps;
    public $isChunked;
    public $isGzip;
    public $isDeflate;

    public function __construct()
    {
        $this->header = [];
        $this->chunkExt = [];
    }

    /**
     * @param Connection $connection
     * @return array|null copy 函数 args
     *  返回null 不需要执行copy, 返回array 执行copy
     */
    public function analyze(Connection $connection)
    {
        if ($this->type == self::REQUEST) {
//            sys_echo(json_encode($this, JSON_PRETTY_PRINT));
        } else if ($this->type === self::RESPONSE) {
//            sys_echo(json_encode($this, JSON_PRETTY_PRINT));
        }
    }

    public function isHttps()
    {

    }

    public function isChunked()
    {
        return isset($this->header["Transfer-Encoding"]) &&
        strtolower($this->header["Transfer-Encoding"]) === "chunked";
    }

    public function isGzip()
    {
        return isset($this->header["Content-Encoding"]) &&
        strtolower($this->header["Content-Encoding"]) === "gzip";
    }

    public function isDeflate()
    {
        return isset($this->header["Content-Encoding"]) &&
        strtolower($this->header["Content-Encoding"]) === "deflate";
    }

    public function finishParsingHeader(Connection $connection)
    {
        $this->isChunked = $this->isChunked();
        $this->isGzip = $this->isGzip();
        $this->isDeflate = $this->isDeflate();

        $this->state = self::STATE_HEADER_FIN;
        $connection->currentPacket = $this;
    }

    public function finishParsingBody()
    {
        $this->state = self::STATE_BODY_FIN;
    }
}