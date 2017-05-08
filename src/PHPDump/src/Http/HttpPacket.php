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
    public $body;

    /**
     * @param Connection $connection
     * @return array|null copy 函数 args
     *  返回null 不需要执行copy, 返回array 执行copy
     */
    public function analyze(Connection $connection)
    {
        if ($this->type == self::REQUEST) {
            var_dump($this);
        } else if ($this->type === self::RESPONSE) {
            var_dump($this);
        }
    }
}