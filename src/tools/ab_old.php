#!/usr/bin/env php
<?php

// php ab.php -c=100 -n=1000 -x=GET -s='http://127.0.0.1:8030/'

$opt = getopt('c::n::s:x::');
$c = isset($opt['c']) ? $opt['c'] : 100;
$n = isset($opt['n']) ? $opt['n'] : 1000;
$s = isset($opt['s']) ? $opt['s'] : "http://www.baidu.com/";
$x = isset($opt['x']) ? $opt['x'] : 'GET';
$d = isset($opt['d']) ? $opt['d'] : '';

if ($x === "GET") {
    AB::get($s, $c, $n);
} else if ($x === "POST") {
    AB::post($s, $d, $c, $n);
}


final class AB
{
    private $host;
    private $port;
    private $tokens;

    public static function get($url, $c, $n)
    {
        list($host, $port, $path) = static::parseUrl($url);

        $self = new static($host, $port);
        $self->makeTokens($n);
        $self->makeClients($c, function(\swoole_http_client $client, $cid) use($self, $path) {
            $self->oneClientGet($cid, $client, $path);
        });
    }

    public static function post($url, $post, $c, $n)
    {
        list($host, $port, $path) = static::parseUrl($url);

        $self = new static($host, $port);
        $self->makeTokens($n);
        $self->makeClients($c, function(\swoole_http_client $client, $cid) use($self, $path, $post) {
            $self->oneClientPost($cid, $client, $path, $post);
        });
    }

    private function __construct($host, $port = 80)
    {
        $this->host = $host;
        $this->port = $port;
    }

    private function makeClients($c, callable $callback)
    {
        swoole_async_dns_lookup($this->host, function($host, $ip) use($callback, $c) {
            $this->ip = $ip;
            for ($i = 0; $i < $c; $i++) {
                $callback(new \swoole_http_client($ip, $this->port), $i);
            }
        });
    }

    // 长连接
    private function oneClientGet($cid, \swoole_http_client $client, $path)
    {
        $token = $this->getToken();
        if ($token === 0) {
            $client->close();
            return;
        }

        $client->get($path, function(\swoole_http_client $client) use($cid, $token, $path) {
            echo "cid=$cid, token=$token, statusCode={$client->statusCode} \n";
            $this->oneClientGet($cid, $client, $path);
        });
    }

    private function oneClientPost($cid, \swoole_http_client $client, $path, &$post)
    {
        $token = $this->getToken();
        if ($token === 0) {
            $client->close();
            return;
        }

        $client->post($path, $post, function(\swoole_http_client $client) use ($cid, $token, $path, &$post) {
            echo "cid=$cid, token=$token, statusCode={$client->statusCode} \n";
            $this->oneClientPost($cid, $client, $path, $post);
        });
    }

    private function makeTokens($n)
    {
        $this->tokens = $n;
    }

    private function getToken()
    {
        if ($this->tokens > 0) {
            $token = $this->tokens;
            $this->tokens--;
            return $token;
        } else {
            return 0;
        }
    }

    private static function parseUrl($url)
    {
        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT) ?: 80;
        $path = parse_url($url, PHP_URL_PATH) ?: "/";
        $query = parse_url($url, PHP_URL_QUERY) ?: "";
        return [$host, $port, "$path?$query"];
    }
}