<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/26
 * Time: 下午9:13
 */

namespace Minimalism\A\Client;

use function Minimalism\A\Core\callcc;

/*
function async_sleep($ms)
{
    return new AsyncSleep($ms);
}

function async_dns($host, $timeo = 100)
{
    return new AsyncDns($host, $timeo);
}
*/


function async_sleep($ms)
{
    return callcc(function($k) use($ms) {
        swoole_timer_after($ms, function() use($k) {
            $k();
        });
    });
}

function async_dns_loohup($host, $timeo = 100)
{
    return callcc(function($k) use($host) {
        $r = swoole_async_dns_lookup($host, function($host, $ip) use($k) {
            $k($ip);
        });
        if (!$r) {
            $k(null, new \BadFunctionCallException("\\swoole_async_dns_lookup"));
        }
    }, $timeo);
}

function async_get($host, $port = 80, $uri = "/", array $headers = [], array $cookies = [], $body = "", $timeo = 1000)
{
    return async_request($host, $port, "GET", $uri, $headers, $cookies, $body, $timeo);
}

function async_post($ip, $port = 80, $uri = "/", array $headers = [], array $cookies = [], $body = "", $timeo = 1000)
{
    return async_request($ip, $port, "POST", $uri, $headers, $cookies, $body, $timeo);
}

function async_request($ip, $port, $method, $uri = "/", array $headers = [], array $cookies = [], $body = "", $timeo = 1000)
{
    return (new AsyncHttpClient($ip, $port))
        ->setMethod($method)
        ->setUri($uri)
        ->setHeaders($headers)
        ->setCookies($cookies)
        ->setData($body)
        ->setTimeout($timeo);
}

function async_mysql_connect(AsyncMysql $mysql, $timeo = 1000)
{
    return $mysql->connect($timeo);
}

function async_mysql_query(AsyncMysql $mysql, $sql, array $bind = [], $timeo = 1000)
{
    return $mysql->query($sql, $bind, $timeo);
}

function async_mysql_begin(AsyncMysql $mysql, $timeo = 1000)
{
    return $mysql->begin($timeo);
}

function async_mysql_commit(AsyncMysql $mysql, $timeo = 1000)
{
    return $mysql->commit($timeo);
}

function async_mysql_rollback(AsyncMysql $mysql, $timeo = 1000)
{
    return $mysql->rollback($timeo);
}

function async_read($file)
{
    return (new AsyncFile($file))->read();
}

function async_write($file, $contents)
{
    return (new AsyncFile($file))->write($contents);
}