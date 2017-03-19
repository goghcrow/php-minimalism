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

function noop()
{

}

function trampoline(callable $f)
{
    $fn = $f;
    while ($fn instanceof Trampoline) {
        $fn = $fn();
    }
    return $fn;
}

/**
 * 执行异步任务
 *
 * @param \Generator|callable|mixed $task
 * @param callable $continuation function($r = null, $ex = null) {}
 * @param AsyncTask $parent
 * @param array $ctx Context可以附加在 \Generator 对象的属性上
 *
 * 说明:
 *  第一个参数为task
 *  对于其余参数
 *      如果参数类型 callable 则参数被设置为 Continuation
 *      如果参数类型 AsyncTask 则参数被设置为 ParentTask
 *      如果参数类型 array 则参数被设置为 Context
 *
 *      由于先检查 callable 再检查 is_array, 所以如果 continuation 以 array 形式传递,
 *      且同时传递了 array 类型的 ctx, 则 continuation 参数顺序要前置于 ctx 参数
 *      array 类型的 ctx 不能构成 callable
 *
 * @example
 *
 * async($task); // 只传递 task, task instanceof \Generator
 * async(function() { yield; }); // 只传递 task, task = call(callable)
 * async(mixed); // 只传递 task
 *
 * async(mixed $task, callable $continuation) // 传递 continuation
 * async(mixed $task, AsyncTask $parent) // 传递 parentTask
 * async(mixed $task, array $ctx) // 传递 context
 *
 * async(mixed $task, callable $continuation, AsyncTask $parent) // 同时传递 continuation 与 parentTask
 * async(mixed $task, AsyncTask $parent, callable $continuation) // 同时传递 continuation 与 parentTask
 *
 * async(mixed $task, AsyncTask $parent, array $ctx) // 同时传递 parentTask 与 ctx
 * async(mixed $task, array $ctx, AsyncTask $parent) // 同时传递 parentTask 与 ctx
 *
 * async(mixed $task, callable $continuation,, array $ctx) // 同时传递 continuation 与 ctx
 *
 * async(mixed $task, callable $continuation, AsyncTask $parent, array $ctx) // 同时传递
 * async(mixed $task, callable $continuation, array $ctx, AsyncTask $parent) // 同时传递
 * async(mixed $task, AsyncTask $parent, callable $continuation, array $ctx) // 同时传递
 */
function async()
{
    $n = func_num_args();
    if ($n === 0) {
        return;
    }

    // 1. 匹配参数类型, 兼容参数传递顺序

    $task = func_get_arg(0);
    $continuation = __NAMESPACE__ . "\\noop";
    $parent = null;
    $ctx = [];

    for ($i = 1; $i < $n; $i++) {
        $arg = func_get_arg($i);
        if (is_callable($arg)) {
            $continuation = $arg;
        } else if ($arg instanceof AsyncTask) {
            $parent = $arg;
        } else if (is_array($arg)) {
            $ctx = $arg;
        } else {
            // ignore
        }
    }

    // 2. 兼容 task 类型

    // 如果为callable, 则直接执行, 发生异常 通过 continuation 传递出去
    if (is_callable($task)) {
        try {
            // 为了参数传递方便, 牺牲了对 task 是 callable 的args支持
            $task = $task(/* ...$args*/);
        } catch (\Exception $ex) {
            $continuation(null, $ex);
            return;
        }
    }

    // 如果为\Generator, 添加ctx, 转为异步执行执行
    if ($task instanceof \Generator) {
        foreach ($ctx as $k => $v) {
            $task->$k = $v;
        }
        (new AsyncTask($task, $parent))->begin($continuation);
    } else {
        // 如果为其余类型, 直接通过 Continuation 返回
        $continuation($task, null);
    }
}

function go()
{
    async(...func_get_args());
}

function chan($n = 0)
{
    if ($n === 0) {
        return new Channel();
    } else {
        return new BufferChannel($n);
    }
}

/**
 * @param callable|Async|\Generator $task
 * @param array ...$args
 * @return \Generator
 *
 * 1. convert callable to \Generator
 * 2. convert IAsync to \Generator
 */
function await($task, ...$args)
{
    if ($task instanceof Async) {
        $async = $task;
        $task = function() use($async) {
            yield $async;
        };
    }

    if (is_callable($task)) {
        $task = $task(...$args);
    }

    return $task;
}



/**
 * call-with-current-continuation
 *
 * 仅能够处理半协程的 call/cc, 比如将k本身传递出去, 没有意义, 无法任意跳转
 *
 * @param callable $fun
 *      $fun 参数会接收到continuation $k
 *      $k的签名: void fun($result = null, \Exception = null)
 *      可以抛出异常或者以同步方式返回值
 * @param null|int $timeout
 * @return Async 可以使用call/cc在async环境中将任务异步接口转换为同步接口
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
 *
 * 超时其实可以去掉
 * 用race可以更干净的实现超时
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
 * 跨父子AsyncTask设置上下文
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
 * 跨父子AsyncTask获取上下文
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
 * Promise.all, parallel
 * @param \Generator[] $tasks
 * @return Syscall
 */
function awaitAll(array $tasks)
{
    foreach ($tasks as &$task) {
        $task = await($task);
    }
    unset($task);

    return new Syscall(function(AsyncTask $parent) use($tasks) {
        if (empty($tasks)) {
            return null;
        } else {
            return new All($tasks, $parent);
        }
    });
}

/**
 * Promise.race, any
 * @param array $tasks
 * @return Syscall
 */
function awaitAny(array $tasks)
{
    foreach ($tasks as &$task) {
        $task = await($task);
    }
    unset($task);

    return new Syscall(function(AsyncTask $parent) use($tasks) {
        if (empty($tasks)) {
            return null;
        } else {
            return new Any($tasks, $parent);
        }
    });
}


/**
 * Promise.all
 * @param array $tasks
 * @return Syscall
 */
function all(array $tasks)
{
    return awaitAll($tasks);
}


/**
 * Promise.race
 * @param array $tasks
 * @return Syscall
 */
function race(array $tasks)
{
    return awaitAny($tasks);
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