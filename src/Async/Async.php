<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/1/31
 * Time: 下午4:21
 */

namespace Minimalism\Async;


use Minimalism\Async\Core\AsyncTask;
use Minimalism\Async\Core\CallCC;
use Minimalism\Async\Core\CancelTaskException;
use Minimalism\Async\Core\IAsync;
use Minimalism\Async\Core\Syscall;

final class Async
{
    /**
     * 执行 function() {} :\Generator 或者 \Generator
     * @param \Generator|callable $task
     * @param callable|null $continuation
     *
     * Context 可以附加在 \Generator 对象的属性上
     */
    public static function exec($task, callable $continuation = null)
    {
        if (is_callable($task)) {
            $task = $task();
        }
        assert($task instanceof \Generator);
        (new AsyncTask($task))->start($continuation);
    }

    // php 7 可以使用立即执行函数表达式 function() {} ()
    // for php 5.x Async::coroutine(function() {});
    public static function coroutine(callable $task, ...$args)
    {
        $g = $task(...$args);
        assert($g instanceof \Generator);
        return $g;
    }

    /**
     * call/cc
     *
     * call-with-current-continuation
     *
     * @param callable $fun
     *      $fun 参数会接收到continuation $k
     *      $k的签名: void fun($result = null, \Exception = null)
     *      可以抛出异常或者以同步方式返回值
     * @param null|int $timeout
     * @return IAsync 可以使用call/cc在async环境中将任务异步接口转换为同步接口
     *
     * 可以使用call/cc在async环境中将任务异步接口转换为同步接口
     */
    public static function callcc(callable $fun, $timeout = 0)
    {
        if ($timeout > 0) {
            // return new CallCCWithTimeout($fun, $timeout);
            // or
            $fun = function($k) use($fun, $timeout) {
                $k = self::once($k);
                swoole_timer_after($timeout, function() use($k) {
                    $k(null, new AsyncTimeoutException());
                });
                $fun($k);
            };
        }

        return new CallCC($fun);
    }

    /**
     * @param \Generator[] $tasks
     * @return Syscall
     */
    public static function awaitAll(array $tasks)
    {
        return new Syscall(function(AsyncTask $task) use($tasks) {
            if (empty($tasks)) {
                return null;
            } else {
                return new All($tasks, $task);
            }
        });
    }

    public static function once(callable $fun)
    {
        $has = false;
        return function(...$args) use($fun, &$has) {
            if ($has === false) {
                $fun(...$args);
                $has = true;
            }
        };
    }

    public static function debug_print_backtrace()
    {
        return new Syscall(function(AsyncTask $task) {
            $i = 1;
            $bt = [];
            do {
                $g = $task->generator;
                $file = isset($g->__FILE__) ? $g->__FILE__ : "unknown";
                $line = isset($g->__LINE__) ? $g->__LINE__ : "unknown";
                $bt[] = "#{$i} {$file}:{$line}";
            } while ($task->parent && ($task = $task->parent) && ++$i);
            fprintf(STDERR, implode(PHP_EOL, $bt));
        });
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return Syscall
     */
    public static function getCtx($key, $default = null)
    {
        return new Syscall(function(AsyncTask $task) use($key, $default) {
            while($task->parent && $task = $task->parent);
            if (isset($task->generator->$key)) {
                return $task->generator->$key;
            } else {
                return $default;
            }
        });
    }

    /**
     * @param string $key
     * @param mixed $val
     * @return Syscall
     */
    public static function setCtx($key, $val)
    {
        return new Syscall(function(AsyncTask $task) use($key, $val) {
            while($task->parent && $task = $task->parent);
            $task->generator->$key = $val;
        });
    }

    public static function cancelTask()
    {
        return new Syscall(function(/*AsyncTask $task*/) {
            throw new CancelTaskException();
        });
    }

    // ⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇ async sleep ⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇

    public static function sleep($ms)
    {
        return new AsyncSleep($ms);
    }

    // ⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇ async http ⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇

    public static function dns($host, $timeo = 100)
    {
        return new AsyncDns($host, $timeo);
    }

    public static function get($host, $port = 80, $uri = "/", array $headers = [], array $cookies = [], $body = "", $timeo = 1000)
    {
        return self::request($host, $port, "GET", $uri, $headers, $cookies, $body, $timeo);
    }

    public static function post($ip, $port = 80, $uri = "/", array $headers = [], array $cookies = [], $body = "", $timeo = 1000)
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