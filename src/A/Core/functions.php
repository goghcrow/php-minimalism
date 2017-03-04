<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/25
 * Time: 下午5:56
 */

namespace Minimalism\A\Core;

use Minimalism\A\Core\Exception\AsyncTimeoutException;
use Minimalism\A\Core\Exception\CancelTaskException;


/**
 * \Generator 执行器
 * 执行 function() {} :\Generator 或者 \Generator
 * @param mixed $task
 * @param callable|null $continuation
 * @param array $ctx Context 可以附加在 \Generator 对象的属性上
 */
function async($task, callable $continuation = null, array $ctx = [])
{
    (new AsyncTask($task))->start($continuation, $ctx);
}

/**
 * php 7 可以使用立即执行函数表达式 function() {} ()
 * for php 5.x coroutine(function() {});
 *
 * @param callable $task
 * @param array ...$args
 * @return mixed
 */
function await(callable $task, ...$args)
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
 * @example
 * 可以使用call/cc在async环境中将任务异步接口转换为同步接口
 *
 * ```php
 * function asyncSleep($ms) {
 *  return callcc(function($k) use($ms) {
 *      swoole_timer_after($ms, function() use($k) {
 *          $k();
 *      });
 *  });
 * }
 *
 * // yield asyncSleep(1000);
 *
 * $result = (yield callcc(function($k) {
 *      doSomethingAsync(function($result) use($k) {
 *      // 通过延续把异步结果返回给yield表达式左值
 *          $k($result);
 *          // or
 *          $k(null, $ex);
 *      });
 * }));
 * ```
 */
function callcc(callable $fun, $timeout = 0)
{
    if ($timeout > 0) {
        $fun = _timeout($fun, $timeout);
    }
    return new CallCC($fun);
}

/**
 * 取消任务
 * @return Syscall
 */
function cancelTask()
{
    return new Syscall(function(/*AsyncTask $task*/) {
        throw new CancelTaskException();
    });
}

/**
 * 设置上下文
 * @param string $key
 * @param mixed $default
 * @return Syscall
 */
function getCtx($key, $default = null)
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
 * 获取上下文
 * @param string $key
 * @param mixed $val
 * @return Syscall
 */
function setCtx($key, $val)
{
    return new Syscall(function(AsyncTask $task) use($key, $val) {
        while($task->parent && $task = $task->parent);
        $task->generator->$key = $val;
    });
}

/**
 * 并行任务
 * @param \Generator[] $tasks
 * @return Syscall
 *
 * @example
 */
function awaitAll(array $tasks)
{
    return new Syscall(function(AsyncTask $parent) use($tasks) {
        if (empty($tasks)) {
            return null;
        } else {
            foreach ($tasks as &$task) {
                if (is_callable($task)) {
                    $task = $task();
                }
                assert($task instanceof \Generator);
            }
            unset($task);
            return new All($tasks, $parent);
        }
    });
}

/**
 * @param callable $fun
 * @param array ...$args
 * @return \Closure
 *
 * @example
 * // 修复callback参数位置
 * function dns($host, $cb) {
 *      swoole_async_dns_lookup($host, function($_, $ip) use($cb) {
 *          $cb($ip);
 *      });
 * }
 *
 * async(function() {
 *      // 转换为thunk
 *      $dns = thunkify(__NAMESPACE__ . "\\dns");
 *      // thunk -> IAsync
 *      $ip = (yield callcc($dns("www.baidu.com")));
 *      echo $ip;
 * });
 *
 */
function thunkify(callable $fun, ...$args)
{
    return function(callable $callback) use($fun, $args) {
        $args[] = $callback;
        return $fun(...$args);
    };
}


/**
 * @internal
 * @param callable $fun
 * @return \Closure
 */
function _once(callable $fun)
{
    $has = false;
    return function(...$args) use($fun, &$has) {
        if ($has === false) {
            $fun(...$args);
            $has = true;
        }
    };
}

/**
 * @internal
 * @param callable $fun
 * @param int $timeout ms
 * @return \Closure
 */
function _timeout(callable $fun, $timeout)
{
    return function($k) use($fun, $timeout) {
        $k = _once($k);
        $fun($k);
        swoole_timer_after($timeout, function() use($k) {
            $k(null, new AsyncTimeoutException());
        });
    };
}

/**
 * @internal
 * @param $k
 * @param $n
 * @return \Closure
 */
//function _kargn($k, $n = -1)
//{
//    return function() use($n, $k) {
//        if ($n === -1) {
//            return $k();
//        } else {
//            return $k(func_get_arg($n));
//        }
//    };
//}