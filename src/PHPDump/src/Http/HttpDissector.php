<?php

namespace Minimalism\PHPDump\Http;


use Minimalism\PHPDump\Buffer\Buffer;
use Minimalism\PHPDump\Pcap\Connection;
use Minimalism\PHPDump\Pcap\PDU;
use Minimalism\PHPDump\Pcap\Dissector;


/**
 * Class HttpProtocol
 * @package Minimalism\PHPDump\Http
 *
 *
 * HTTP Method	RFC	Request Has Body	Response Has Body	Safe	Idempotent	Cacheable
 * GET	        RFC 7231	No	        Yes	                Yes	    Yes	        Yes
 * HEAD	        RFC 7231	No	        No	                Yes	    Yes	        Yes
 * POST	        RFC 7231	Yes	        Yes	                No	    No	        Yes
 * PUT	        RFC 7231	Yes	        Yes	                No	    Yes	        No
 * DELETE	    RFC 7231	No	        Yes	                No	    Yes	        No
 * CONNECT	    RFC 7231	Yes	        Yes	                No	    No	        No
 * OPTIONS	    RFC 7231	Optional	Yes	                Yes	    Yes	        No
 * TRACE	    RFC 7231	No	        Yes	                Yes	    Yes	        No
 * PATCH	    RFC 5789	Yes	        Yes	                No	    No	        Yes
 *
 * RFC7230 - RFC7237
 * https://tools.ietf.org/rfc/index
 *
 * media-types
 * http://www.iana.org/assignments/media-types/media-types.xhtml
 *
 *
 * OWS            = *( SP / HTAB )
 *                 ; optional whitespace
 * RWS            = 1*( SP / HTAB )
 *                 ; required whitespace
 * BWS            = OWS
 *                 ; "bad" whitespace
 *
 * token          = 1*tchar
 * tchar          = "!" / "#" / "$" / "%" / "&" / "'" / "*"
 *                  / "+" / "-" / "." / "^" / "_" / "`" / "|" / "~"
 *                  / DIGIT / ALPHA
 *                  ; any VCHAR, except delimiters
 *
 * ------------------------------------------------------------------
 *
 * HTTP-message   = start-line
 *                  *( header-field CRLF )
 *                  CRLF
 * [ message-body ]
 * ------------------------------------------------------------------
 * start-line     = request-line / status-line
 * ------------------------------------------------------------------
 * request-line   = method SP request-target SP HTTP-version CRLF
 * ------------------------------------------------------------------
 * method         = token
 * ------------------------------------------------------------------
 * status-line    = HTTP-version SP status-code SP reason-phrase CRLF
 * ------------------------------------------------------------------
 * status-code    = 3DIGIT
 * ------------------------------------------------------------------
 * reason-phrase  = *( HTAB / SP / VCHAR / obs-text )
 * ------------------------------------------------------------------
 * header-field   = field-name ":" OWS field-value OWS
 * ------------------------------------------------------------------
 * field-name     = token
 *
 * field-value    = *( field-content / obs-fold )
 * field-content  = field-vchar [ 1*( SP / HTAB ) field-vchar ]
 * field-vchar    = VCHAR / obs-text
 *
 * obs-fold       = CRLF 1*( SP / HTAB )
 *                  ; obsolete line folding
 * ------------------------------------------------------------------
 * message-body = *OCTET
 * ------------------------------------------------------------------
 * Transfer-Encoding = 1#transfer-coding        ; Transfer-Encoding: gzip, chunked
 * Content-Length = 1*DIGIT                     ; Content-Length: 3495
 * ------------------------------------------------------------------
 * request-target = origin-form
 *                  / absolute-form
 *                  / authority-form
 *                  / asterisk-form
 * origin-form    = absolute-path [ "?" query ] ; GET /where?q=now HTTP/1.1
 * absolute-form  = absolute-URI                ; GET http://www.example.org/pub/WWW/TheProject.html HTTP/1.1
 * authority-form = authority                   ; CONNECT www.example.com:80 HTTP/1.1
 * asterisk-form  = "*"                         ; OPTIONS * HTTP/1.1
 * ------------------------------------------------------------------
 * Host = uri-host [ ":" port ]
 *
 *
 *
 * length := 0
 * read chunk-size, chunk-ext (if any), and CRLF
 * while (chunk-size > 0) {
 *      read chunk-data and CRLF
 *      append chunk-data to decoded-body
 *      length := length + chunk-size
 *      read chunk-size, chunk-ext (if any), and CRLF
 * }
 * read trailer field
 * while (trailer field is not empty) {
 *      if (trailer field is allowed to be sent in a trailer) {
 *           append trailer field to existing header fields
 *      }
 *      read trailer-field
 * }
 * Content-Length := length
 * Remove "chunked" from Transfer-Encoding
 * Remove Trailer from existing header fields
 *
 *
 *
 * OLD:
 * ==================================================================
 * HTTP-message   = Request | Response     ; HTTP/1.1 messages
 * ------------------------------------------------------------------
 * generic-message = start-line
 *                  *(message-header CRLF)
 *                  CRLF
 * [ message-body ]
 * ------------------------------------------------------------------
 * start-line      = Request-Line | Status-Line
 * ------------------------------------------------------------------
 * message-header = field-name ":" [ field-value ]
 * field-name     = token
 * field-value    = *( field-content | LWS )
 * field-content  = <the OCTETs making up the field-value
 *                  and consisting of either *TEXT or combinations
 *                  of token, separators, and quoted-string>
 * ------------------------------------------------------------------
 * message-body = entity-body
 *                 | <entity-body encoded as per Transfer-Encoding>
 * ------------------------------------------------------------------
 * general-header = Cache-Control
 *                  | Connection
 *                  | Date
 *                  | Pragma
 *                  | Trailer
 *                  | Transfer-Encoding
 *                  | Upgrade
 *                  | Via
 *                  | Warning
 *
 *
 *
 * Request       =  Request-Line
 *                  *(( general-header
 *                  | request-header
 *                  | entity-header ) CRLF)
 *                  CRLF
 * [ message-body ]
 *
 *
 * Request-Line   = Method SP Request-URI SP HTTP-Version CRLF
 *
 * Method         =   "OPTIONS"
 *                  | "GET"
 *                  | "HEAD"
 *                  | "POST"
 *                  | "PUT"
 *                  | "DELETE"
 *                  | "TRACE"
 *                  | "CONNECT"
 *                  | extension-method
 * extension-method = token
 *
 * Request-URI    = "*" | absoluteURI | abs_path | authority
 *
 * ==================================================================
 * Response      =  Status-Line
 *                  *(( general-header
 *                  | response-header
 *                  | entity-header ) CRLF)
 *                  CRLF
 * [ message-body ]
 *
 * Status-Line = HTTP-Version SP Status-Code SP Reason-Phrase CRLF
 */
class HttpDissector implements Dissector
{
    const CRLF = "\r\n";
    const CRLF2 = "\r\n\r\n";
    const MAX_HEADER_SIZE = 1024 * 8 + 4;

    private static $maxMethodSize;
    private static $methods = [
        "GET"       => 3,
        "HEAD"      => 4,
        "POST"      => 4,
        "PUT"       => 3,
        "DELETE"    => 6,
        "TRACE"     => 5,
        "OPTIONS"   => 7,
        "CONNECT"   => 7,
        "PATCH"     => 5,
    ];

    public function __construct()
    {
        if (self::$maxMethodSize === null) {
            $t = array_map("strlen", array_keys(self::$methods));
            rsort($t);
            self::$maxMethodSize = $t[0];
        }
    }

    public function getName()
    {
        return "HTTP";
    }

    /**
     * @param Connection $connection
     * @return bool
     */
    public function detect(Connection $connection)
    {
        $buffer = $connection->buffer;
        if ($buffer->readableBytes() < self::$maxMethodSize) {
            return Dissector::DETECT_WAIT;
        }


        $t = $buffer->get(self::$maxMethodSize);

        if (substr($t, 0, 4) === "HTTP") {
            return Dissector::DETECTED;
        } else {
            foreach (self::$methods as $method => $len) {
                if (substr($t, 0, $len) === $method) {
                    return Dissector::DETECTED;
                }
            }
            return Dissector::UNDETECTED;
        }
    }

    /**
     * @param Connection $connection
     * @return bool
     */
    public function isReceiveCompleted(Connection $connection)
    {
        /* @var $packet HttpPDU */

        $buffer = $connection->buffer;

        if ($packet = $connection->currentPacket) {
            if ($packet->state === HttpPDU::STATE_BODY_FIN) {
                assert($connection->currentPacket);
                return true;
            }

            assert($packet->state === HttpPDU::STATE_HEADER_FIN);
            if ($packet->isChunked) {
                return self::parseChunked($buffer, $packet);
            } else {
                assert(isset($packet->header["Content-Length"]));
                $contentLen = intval($packet->header["Content-Length"]);
                if ($buffer->readableBytes() >= $contentLen) {
                    $packet->body = $buffer->read($contentLen);
                    $packet->finishParsingBody();
                    return true;
                } else {
                    return false;
                }
            }

        } else {
            // 检测是否收到完整的header
            $raw = $buffer->get(self::MAX_HEADER_SIZE);
            if (strpos($raw, self::CRLF2) === false) {
                return false; // wait
            }

            $packet = self::parseHeader($connection);

            // TODO 这里需要假设都有body
            // 没有content-length继续读...
            if ($this->hasBody($packet)) {
                if ($packet->isChunked) {
                    $packet->header["Content-Length"] = 0;
                    return self::parseChunked($buffer, $packet);
                } else {
                    $header = $packet->header;
                    if (isset($header["Content-Length"])) {
                        $contentLen = intval($header["Content-Length"]);
                        if ($buffer->readableBytes() >= $contentLen) {
                            $packet->body = $buffer->read($contentLen);
                            $packet->finishParsingBody();
                            return true;
                        } else {
                            return false;
                        }
                    } else {
                        // http 1.0 或者 close 可以无content-length 断开连接即可，无粘包问题
                        $isHttp10 = $packet->httpVer === "HTTP/1.0";
                        $isConnectionClosed = isset($packet->header["Connection"]) && strtolower($packet->header["Connection"]) === "close";

                        if ($isHttp10 || $isConnectionClosed) {
                            // 延迟到连接关闭
                            $connection->on(Connection::EVT_CLOSE, function() use($connection) {
                                if ($connection->currentPacket) {
                                    /* @var HttpPDU $packet */
                                    $packet = $connection->currentPacket;
                                    $packet->finishParsingBody();
                                    $connection->doDissect();
                                }
                            });
                            return false;
                        } else {
                            print_r($packet);
                            sys_abort("http missing content-length");
                        }
                    }
                }
            } else {
                $state = $this->detect($connection);
                switch ($state) {
                    case Dissector::DETECT_WAIT:
                    case Dissector::DETECTED:
                    default:
                        $packet->finishParsingBody();
                        return true;
                        break;
                    case Dissector::UNDETECTED:
                        print_r($packet);
                        sys_abort("malformed http message:" . $buffer->readFull());
                }
            }
        }
    }

    /**
     * @param Connection $connection
     * @return PDU
     */
    public function dissect(Connection $connection)
    {
        // 直接从当前连接获取完整的包

        /* @var $packet HttpPDU */
        $packet = $connection->currentPacket;
        $connection->currentPacket = null;

        assert($packet !== null);
        assert($packet->state === HttpPDU::STATE_BODY_FIN);

        if ($packet->isGzip) {
            $packet->body = $this->gzDecode($packet->body);
        } else if ($packet->isDeflate) {
            $packet->body = gzinflate($packet->body); // TODO test ?!
        }
        return $packet;
    }

    /**
     * WTF
     * @param $str
     * @return string
     * gzencode() uses the GZIP file format, the same as the gzip command line tool. This file format has a header containing optional metadata, DEFLATE compressed data, and footer containing a CRC32 checksum and length check.
     * gzcompress() uses the ZLIB format. It has a shorter header serving only to identify the compression format, DEFLATE compressed data, and a footer containing an ADLER32 checksum.
     * gzdeflate() uses the raw DEFLATE algorithm on its own, which is the basis for both of the other formats.
     */
    private function gzDecode($str)
    {
        $r = gzdecode($str);
        if ($r === false) {
            $r = gzinflate($str);
            if ($r === false) {
                $r = gzuncompress($str);
            }
            // gzinflate(substr($str,10,-8)); ?? wtf
        }
        return $r;
    }

    /**
     * @param HttpPDU $packet
     * @return bool
     *
     * NOTICE: 如果有违反RFC的情况，这里会出错
     */
    private function hasBody(HttpPDU $packet)
    {
        if ($packet->type === HttpPDU::REQUEST) {
            return in_array($packet->method, ["POST", "PUT", "CONNECT", "PATCH", "OPTIONS"], true);
        } else if ($packet->type === HttpPDU::RESPONSE) {
            return $packet->method !== "HEAD";
        }
        assert(false);
    }

    private static function parseHeader(Connection $connection)
    {
        $buffer = $connection->buffer;

        // 读取全部parseHeader
        $raw = $buffer->readFull();

        $packet = new HttpPDU($connection);
        assert(strpos($raw, self::CRLF2) !== false);

        list($headerStr, $_/*$packet->body*/) = explode(self::CRLF2, $raw, 2);
        $buffer->write($_); // body 重新写回buffer

        $lines = explode(self::CRLF, $headerStr);
        if (empty($lines)) {
            sys_abort("malformed http start line: $headerStr");
        }

        /* {{{ decodeFirstLine */
        $line = explode(" ", $lines[0], 3);
        if (count($line) !== 3) {
            sys_abort("malformed http start line: $headerStr");
        }
        if (empty($line[0]) || empty($line[1]) || empty($line[2])) {
            sys_abort("malformed http start line: $headerStr");
        }
        /* }}} */

        if (substr($line[0], 0, 4) === "HTTP") {
            $packet->type = HttpPDU::RESPONSE;
            $packet->statusLine = trim($lines[0]);
            list($packet->httpVer, $packet->statusCode, $packet->reasonPhrase) = $line;
        } else {
            $packet->type = HttpPDU::REQUEST;
            $packet->requestLine = trim($lines[0]);
            list($packet->method, $packet->uri, $packet->httpVer) = $line;
        }
        unset($lines[0]);

        foreach ($lines as $line) {
            $r = explode(':', trim($line), 2);
            if (count($r) !== 2) { // v其实可选
                continue;
            }
            $packet->header[trim(ucwords(strtolower($r[0]), "-"))] = trim($r[1]);
        }

        $packet->finishParsingHeader();

        return $packet;
    }

    private static function parseChunked(Buffer $buffer, HttpPDU $packet)
    {
        while (true) {
            $raw = $buffer->get(PHP_INT_MAX);
            $pos = strpos($raw, self::CRLF);
            if ($pos === false) {
                return false;
            } else {
                $chunkLine = $buffer->read($pos);
                self::readCRLF($buffer);

                $chunkLineItems = explode(";", $chunkLine);
                if (empty($chunkLineItems)) {
                    sys_abort("malformed http chunk line: $chunkLine");
                }
                $chunkSize = hexdec(trim($chunkLineItems[0]));
                unset($chunkLineItems[0]);

                foreach ($chunkLineItems as $line) {
                    $r = explode(':', trim($line), 2);
                    if (count($r) !== 2) { // v其实可选
                        continue;
                    }
                    $packet->chunkExt[trim(ucwords($r[0], "-"))] = trim($r[1]);
                }

                $lastChunk = $chunkSize === 0;
                if ($lastChunk) {
                    // TODO: read chunkedTrailer
                    self::readCRLF($buffer);
                    // append to header
                    // Remove "chunked" from Transfer-Encoding
                    // Remove Trailer from existing header fields
                    $packet->finishParsingBody();
                    return true;
                } else {
                    if ($buffer->readableBytes() >= $chunkSize) {
                        $packet->header["Content-Length"] += $chunkSize;
                        $packet->body .= $buffer->read($chunkSize);
                        self::readCRLF($buffer);
                        continue;
                    } else {
                        return false;
                    }
                }
            }
        }

        assert(false);
    }

    private static function readCRLF(Buffer $buffer)
    {
        assert($buffer->readableBytes() >= 2);
        $crlf = $buffer->read(2);
        // assert($crlf === self::CRLF);
        if ($crlf !== self::CRLF) {
            debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            sys_abort("malformed chunk message: " . $buffer->readFull());
        }
    }
}