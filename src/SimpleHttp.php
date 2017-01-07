<?php

namespace Blue;


class SimpleHttp
{
    public static function request($url, $method, array $header, $body, $timeout = 60)
    {
        $header = $header + ["Connection" => "close", "Content-Length" => strlen($body),];

        $header = array_reduce(array_keys($header), function($curry, $k) use($header) {
            return $curry . "$k: $header[$k]\r\n";
        }, "");

        $opts = [
            "http" => [
                "method"  => $method,
                "header"  => $header,
                "content" => $body,
                "timeout" => $timeout
            ],
            "ssl" => [
                "verify_peer" => false,
                "verify_peer_name" => false,
            ]
        ];
        $ctx  = stream_context_create($opts);

        // echo  "$method $url\n";
        // 注意顺序, file_get_contents 之后才可以使用$http_response_header
        return [
            "body" => file_get_contents($url, false, $ctx),
            "header" => $http_response_header,
        ];
    }

    public static function get($url, array $header = [], array $query = [], $timeout = 60)
    {
        $query = http_build_query($query);
        return static::request("$url?$query", "GET", $header, "", $timeout);
    }

    public static function post($url, array $header, $body, $timeout = 60)
    {
        return static::request($url, "POST", $header, $body, $timeout);
    }
}