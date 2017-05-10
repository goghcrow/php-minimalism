<?php

namespace Minimalism\PHPDump\Http;


use Minimalism\PHPDump\Pcap\Connection;
use Minimalism\PHPDump\Pcap\Packet;
use Minimalism\PHPDump\Util\T;


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

    public $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->header = [];
        $this->chunkExt = [];
    }

    public function analyze(Connection $connection)
    {
        $srcIp = $connection->IPHdr->source_ip;
        $dstIp = $connection->IPHdr->destination_ip;
        $srcPort = $connection->TCPHdr->source_port;
        $dstPort = $connection->TCPHdr->destination_port;

        $src = T::format("$srcIp:$srcPort", T::BRIGHT);
        $dst = T::format("$dstIp:$dstPort", T::BRIGHT);

        $sec = $connection->recordHdr->ts_sec;
        $usec = $connection->recordHdr->ts_usec;

        // sys_echo(json_encode($this, JSON_PRETTY_PRINT));

        $type = "Unknown";
        $firstLine = "";
        $curl = null;
        if ($this->type == self::REQUEST) {
            $type = "REQUEST";
            $firstLine = T::format($this->requestLine, T::FG_GREEN);
            $curl = $this->asCurl();
        } else if ($this->type === self::RESPONSE) {
            $type = "RESPONSE";
            $firstLine = T::format($this->statusLine, T::FG_GREEN);
        }


        $type = T::format($type, T::FG_GREEN);
        sys_echo("$src > $dst", $sec, $usec);

        $header = [];
        foreach ($this->header as $k => $v) {
            $header[] = "$k: $v\n";
        }
        $header = implode("", $header);

        echo $firstLine, "\n";
        echo $header, "\n";
        if ($this->body) {
            echo T::format($this->body, T::DIM), "\n";
        }

        if ($curl) {
            echo $curl;
        }

        echo "\n";
    }

    public function finishParsingHeader()
    {
        $this->isChunked();
        $this->isGzip();
        $this->isDeflate();
        $this->isHttps();

        $this->state = self::STATE_HEADER_FIN;
        $this->connection->currentPacket = $this;
    }

    public function finishParsingBody()
    {
        $this->state = self::STATE_BODY_FIN;
    }

    private function isHttps()
    {
        $TCPHdr = $this->connection->TCPHdr;
        $this->isHttps = $TCPHdr->source_port === 443 || $TCPHdr->destination_port === 443;
    }

    private function isChunked()
    {
        $this->isChunked = isset($this->header["Transfer-Encoding"]) &&
            strtolower($this->header["Transfer-Encoding"]) === "chunked";
    }

    private function isGzip()
    {
        $this->isGzip = isset($this->header["Content-Encoding"]) &&
            strtolower($this->header["Content-Encoding"]) === "gzip";
    }

    private function isDeflate()
    {
        $this->isDeflate = isset($this->header["Content-Encoding"]) &&
            strtolower($this->header["Content-Encoding"]) === "deflate";
    }

    public function asCurl()
    {
        $curl = "";

        if ($this->type === self::REQUEST) {
            $curl .= "curl ";

            if ($this->isHttps) {
                $curl .= "'https://";
            } else {
                $curl .= "'http://";
            }

            $ip = $this->connection->IPHdr->destination_ip;
            $port = $this->connection->TCPHdr->destination_port;

            if (isset($this->header["Host"])) {
                $host = $this->header["Host"];

                $curl .= $host;
                if (strpos($host, ":") === false && intval($port) != 80) {
                    $curl .= ":$port";
                }
            } else {
                $curl .= "$ip";
                if ($port != 80) {
                    $curl .= ":$port";
                }
            }

            $curl .= "{$this->uri}'";

            if ($this->method !== "GET") {
                $curl .= " -X {$this->method}";
            }

            $header = [];
            foreach ($this->header as $k => $v) {
                if ($k === "Content-Length") {
                    continue;
                }
                $header[] = "-H '$k: $v'";
            }
            $header = implode(" ", $header);

            if ($header) {
                $curl .= " $header";
            }

            if (strlen($this->body) > 0) {
                $curl .= " --data '{$this->body}'";
            }

            $curl .= " --compressed\n";
        }

        return $curl;
    }
}