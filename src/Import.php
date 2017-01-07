<?php

namespace Blue;


class Import
{
    public static function github($url)
    {
        $file = __DIR__ . "/deps" . parse_url($url, PHP_URL_PATH);
        if (!file_exists($file)) {
            @mkdir(dirname($file), 0777, true);
            file_put_contents($file, static::httpGet($url));
        }
        require_once $file;
    }

    private static function httpGet($url, $timeout = 10)
    {
        $opts = [
            "http" => [
                "method"  => "GET",
                "timeout" => $timeout
            ],
            "ssl" => [
                "verify_peer" => false,
                "verify_peer_name" => false,
            ]
        ];
        $ctx  = stream_context_create($opts);
        return file_get_contents($url, false, $ctx);
    }
}