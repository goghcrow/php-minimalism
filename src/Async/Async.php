<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/1/31
 * Time: 下午4:21
 */

namespace Minimalism\Async;


use Minimalism\Async\Core\AsyncTask;
use Minimalism\Async\Core\Syscall;

final class Async
{
    /**
     * 执行 function() {} :\Generator 或者 \Generator
     * @param \Generator|callable $task
     * @param callable|null $complete
     * @param \stdClass $ctx
     */
    public static function exec($task, callable $complete = null, \stdClass $ctx = null)
    {
        if (is_callable($task)) {
            $task = $task();
        }
        assert($task instanceof \Generator);
        (new AsyncTask($task, $ctx ?: new \stdClass))->start($complete);
    }

    // for php 5.x
    // php 7 function() {} ()
    public static function coroutine(callable $task)
    {
        return $task();
    }

    public static function getCtx($key, $default = null)
    {
        return new Syscall(function(AsyncTask $task) use($key, $default) {
            return isset($task->context->$key) ? $task->context->$key : $default;
        });
    }

    public static function setCtx($key, $val)
    {
        return new Syscall(function(AsyncTask $task) use($key, $val) {
            $task->context->$key = $val;
        });
    }

    public static function sleep($ms)
    {
        return new AsyncSleep($ms);
    }

    // ⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇ async http ⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇

    public static function dns($host, $timeo = 100)
    {
        return new AsyncDns($host, $timeo);
    }

    public static function get($host, $port, $uri = "/", array $headers = [], array $cookies = [], $body = "", $timeo = 1000)
    {
        return self::request($host, $port, "GET", $uri, $headers, $cookies, $body, $timeo);
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

    // ⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇ async mysql ⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇

    public static function mysql_connect(AsyncMysql $mysql, $timeo = 1000)
    {
        return $mysql->connect($timeo);
    }

    public static function mysql_query(AsyncMysql $mysql, $sql, array $bind = [], $timeo = 1000)
    {
        return $mysql->query($sql, $bind, $timeo);
    }

    public static function mysql_begin(AsyncMysql $mysql, $timeo = 1000)
    {
        return $mysql->begin($timeo);
    }

    public static function mysql_commit(AsyncMysql $mysql, $timeo = 1000)
    {
        return $mysql->commit($timeo);
    }

    public static function mysql_rollback(AsyncMysql $mysql, $timeo = 1000)
    {
        return $mysql->rollback($timeo);
    }

    // ⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇ async file ⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇

    public static function read($file)
    {
        return (new AsyncFile($file))->read();
    }

    public static function write($file, $contents)
    {
        return (new AsyncFile($file))->write($contents);
    }
}