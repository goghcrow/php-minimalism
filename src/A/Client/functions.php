<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/26
 * Time: 下午9:13
 */

namespace Minimalism\A\Client;

use function Minimalism\A\Core\gen;
use function Minimalism\A\Core\callcc;
use function Minimalism\A\Core\race;

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
            $k(null);
        });
    });
}

function delay($ms)
{
    return async_sleep($ms);
}

function async_timeout($task, $ms, \Exception $ex = null)
{
    $timerId = null;
    yield race([
        callcc(function($k) use($ms, $ex, &$timerId) {
            $timerId = swoole_timer_after($ms, function() use($k, $ex) {
                $k(null, $ex);
            });
        }),
        function() use (&$timerId, $task){
            yield gen($task);
            if (swoole_timer_exists($timerId)) {
                swoole_timer_clear($timerId);
            }
        },
    ]);
}

function async_dns_lookup($host, $timeo = 100)
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

function async_curl_get($host, $port = 80, $uri = "/", array $headers = [], array $cookies = [], $body = "", $timeo = 1000)
{
    yield async_curl_request($host, $port, "GET", $uri, $headers, $cookies, $body, $timeo);
}

function async_curl_post($host, $port = 80, $uri = "/", array $headers = [], array $cookies = [], $body = "", $timeo = 1000)
{
    yield async_curl_request($host, $port, "POST", $uri, $headers, $cookies, $body, $timeo);
}

function async_curl_request($host, $port, $method, $uri = "/", array $headers = [], array $cookies = [], $body = "", $timeo = 1000)
{
    yield (new AsyncHttpClient((yield async_dns_lookup($host)), $port))
        ->setMethod($method)
        ->setUri($uri)
        ->setHeaders($headers + ["Host" => $host])
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
    return $mysql->start($timeo);
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