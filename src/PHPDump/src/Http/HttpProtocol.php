<?php

namespace Minimalism\PHPDump\Http;


use Minimalism\PHPDump\Buffer\Buffer;
use Minimalism\PHPDump\Pcap\Packet;
use Minimalism\PHPDump\Pcap\Protocol;


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
class HttpProtocol implements Protocol
{
    const CRLF = "\r\n";
    const CRLF2 = "\r\n\r\n";

    private static $methods = [
        "GET",
        "HEAD",
        "POST",
        "PUT",
        "DELETE",
        "TRACE",
        "OPTIONS",
        "CONNECT",
        "PATCH",
    ];

    public function getName()
    {
        return "HTTP";
    }

    private function hasBody(HttpPacket $packet)
    {
        if ($packet->type === HttpPacket::REQUEST) {
            return in_array($packet->method, ["POST", "PUT", "CONNECT", "PATCH", "OPTIONS"], true);
        } else if ($packet->type === HttpPacket::RESPONSE) {
            return $packet->method !== "HEAD";
        }
        assert(false);
    }

    /**
     * @param Buffer $connBuffer
     * @return bool
     */
    public function isReceiveCompleted(Buffer $connBuffer)
    {
        $connBuffer->readableBytes();
        $raw = $connBuffer->get(PHP_INT_MAX);
        if (strpos($raw, self::CRLF2) === false) {
            return false; // wait
        }

        $packet = self::parseHeader($raw);

        if ($this->hasBody($packet)) {
            // TODO chunked
            if (isset($packet["Content-Length"])) {
                $contentLen = intval($packet["Content-Length"]);
                if ($contentLen > strlen($packet->body)) {
                    return false; // wait
                } else {
                    return true;
                }
            } else {
                sys_abort("http missing content-length");
            }
        } else {
            return true;
        }
    }

    /**
     * @param Buffer $buffer
     * @return bool
     */
    public function detect(Buffer $buffer)
    {
        return false;
        // TODO
        "HTTP/";
        // TODO: Implement detect() method.
    }

    /**
     * @param Buffer $connBuffer
     * @return Packet
     */
    public function unpack(Buffer $connBuffer)
    {
        $raw = $connBuffer->readFull();
        $packet = self::parseHeader($raw);

        if ($this->hasBody($packet)) {
            $contentLen = intval($packet["Content-Length"]);
            $packet->body = substr($packet->body, 0, $contentLen);

            $left = substr($packet->body, $contentLen);
            $connBuffer->write($left);
        } else {
            $connBuffer->write($packet->body);
            unset($packet->body);
        }

        return $packet;
    }

    private static function parseHeader($raw)
    {
        $packet = new HttpPacket();
        assert(strpos($raw, self::CRLF2) !== false);

        list($headerStr, $packet->body) = explode("\r\n\r\n", $raw, 2);
        $lines = explode(self::CRLF, $headerStr);
        if (empty($lines)) {
            sys_abort("malformed http start line: $headerStr");
        }

        $line = explode(" ", $lines[0], 3);
        if (count($line) !== 3) {
            sys_abort("malformed http start line: $headerStr");
        }
        if (empty($line[0]) || empty($line[1]) || empty($line[2])) {
            sys_abort("malformed http start line: $headerStr");
        }

        if (substr($line[0], 0, 4) === "HTTP") {
            $packet->type = HttpPacket::RESPONSE;
            $packet->statusLine = trim($lines[0]);
            list($packet->httpVer, $packet->statusCode, $packet->reasonPhrase) = $line;
        } else {
            $packet->type = HttpPacket::REQUEST;
            $packet->requestLine = trim($lines[0]);
            list($packet->method, $packet->uri, $packet->httpVer) = $line;
        }
        unset($lines[0]);

        foreach ($lines as $line) {
            $r = explode(':', trim($line), 2);
            if (count($r) !== 2) { // v其实可选
                continue;
            }
            $packet->header[trim(ucwords($r[0], "-"))] = trim($r[1]);
        }

        return $packet;
    }
}