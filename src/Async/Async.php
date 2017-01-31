<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/1/31
 * Time: 下午4:21
 */

namespace Minimalism\Async;


use Minimalism\Async\Core\AsyncTask;

final class Async
{
    /**
     * @param \Generator|callable $task
     * @param callable|null $complete
     */
    public static function exec($task, callable $complete = null)
    {
        if (is_callable($task)) {
            $task = $task();
        }
        assert($task instanceof \Generator);
        (new AsyncTask($task))->start($complete);
    }

    public static function sleep($ms)
    {
        return new AsyncSleep($ms);
    }

    public static function dns($host, $timeo = 100)
    {
        return new AsyncDns($host, $timeo);
    }

    public static function get($ip, $port, $uri = "/", array $headers = [], array $cookies = [], $body = "", $timeo = 1000)
    {
        return self::request($ip, $port, "GET", $uri, $headers, $cookies, $body, $timeo);
    }

    public static function post($ip, $port, $uri = "/", array $headers = [], array $cookies = [], $body = "", $timeo = 1000)
    {
        return self::request($ip, $port, "POST", $uri, $headers, $cookies, $body, $timeo);
    }

    public static function request($ip, $port, $method, $uri = "/", array $headers = [], array $cookies = [], $body = "", $timeo = 1000)
    {
        return (new AsyncHttpClient($ip, $port))
            ->setMethod($method)
            ->setUri($uri)
            ->setHeaders($headers)
            ->setCookies($cookies)
            ->setData($body)
            ->setTimeout($timeo);
    }
}