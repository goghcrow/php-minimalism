<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/25
 * Time: 下午5:56
 */

namespace Minimalism\A\Core;

use Minimalism\A\Core\Exception\AsyncTimeoutException;

function noop()
{
}

function noopGen()
{
    yield;
}

function isGenFun(callable $fn)
{
    return Gen::isGenFun($fn);
}

function gen($task, ...$args)
{
    return Gen::from($task, ...$args);
}

function closure(callable $callable)
{
    return Closure::fromCallable($callable);
}


/**
 * @deprecated
 *
 * spawn one semicoroutine
 *
 * @internal param callable|\Generator|mixed $task
 * @internal param callable $continuation function($r = null, $ex = null) {}
 * @internal param AsyncTask $parent
 * @internal param array $ctx Context可以附加在 \Generator 对象的属性上
 *
 * 说明:
 *  第一个参数为task
 *  剩余参数 优先检查callable 无顺序要求
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
 * spawn($task); // 只传递 task, task instanceof \Generator
 * spawn(function() { yield; }); // 只传递 task, task = call(callable)
 * spawn(mixed); // 只传递 task
 *
 * spawn(mixed $task, callable $continuation) // 传递 continuation
 * spawn(mixed $task, AsyncTask $parent) // 传递 parentTask
 * spawn(mixed $task, array $ctx) // 传递 context
 *
 * spawn(mixed $task, callable $continuation, AsyncTask $parent) // 同时传递 continuation 与 parentTask
 * spawn(mixed $task, AsyncTask $parent, callable $continuation) // 同时传递 continuation 与 parentTask
 *
 * spawn(mixed $task, AsyncTask $parent, array $ctx) // 同时传递 parentTask 与 ctx
 * spawn(mixed $task, array $ctx, AsyncTask $parent) // 同时传递 parentTask 与 ctx
 *
 * spawn(mixed $task, callable $continuation,, array $ctx) // 同时传递 continuation 与 ctx
 *
 * spawn(mixed $task, callable $continuation, AsyncTask $parent, array $ctx) // 同时传递
 * spawn(mixed $task, callable $continuation, array $ctx, AsyncTask $parent) // 同时传递
 * spawn(mixed $task, AsyncTask $parent, callable $continuation, array $ctx) // 同时传递
 */
function spawn()
{
    $n = func_num_args();
    if ($n === 0) {
        return;
    }

    // 1. 匹配参数类型, 兼容参数传递顺序

    $task = func_get_arg(0);
    $continuation = null;
    $parent = null;
    $ctx = [];

    for ($i = 1; $i < $n; $i++) {
        $arg = func_get_arg($i);
        if (is_callable($arg)) {
            $continuation = $arg;
        } else if ($arg instanceof Task) {
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
            if (is_callable($continuation)) {
                $continuation(null, $ex);
            } else {
                throw $ex;
            }
            return;
        }
    }

    // 如果为\Generator, 添加ctx, 转为异步执行执行
    if ($task instanceof \Generator) {
        foreach ($ctx as $k => $v) {
            $task->$k = $v;
        }

        $asyncTask = new Task($task, $parent);
        $task->__self__ = [$asyncTask]; // hack
        if (is_callable($continuation)) {
            $asyncTask->start($continuation);
        } else {
            $asyncTask->start();
        }
    } else {
        if (is_callable($continuation)) {
            // 如果为其余类型, 直接通过 Continuation 返回
            $continuation($task, null);
        }
    }
}

function run(...$args)
{
    Task::run(...$args);
}

function go(...$args)
{
    Task::run(...$args);
}

function fork($task)
{
    return Task::fork($task);
}

function chan($n = 0)
{
    return Task::chan($n);
}

function callcc(callable $fun, $timeout = 0)
{
    if ($timeout > 0) {
        $fun = _timeout($fun, $timeout);
    }
    return Task::callcc($fun, $timeout);
}

function cancelTask()
{
    return Task::cancel();
}

function getCtx($key, $default = null)
{
    return Task::getCtx($key, $default);
}

function setCtx($key, $val)
{
    return Task::setCtx($key, $val);
}

function waitAll(array $tasks, $ms = 0)
{
    return Task::whenAll($tasks, $ms);
}

function all(array $tasks, $ms = 0)
{
    return Task::whenAll($tasks, $ms);
}

function parallel(array $tasks, $ms = 0)
{
    return Task::whenAll($tasks, $ms);
}

function whenAny(array $tasks, $ms = 0)
{
    return Task::whenAny($tasks, $ms);
}

function race(array $tasks, $ms = 0)
{
    return Task::whenAny($tasks, $ms);
}

function defer(callable $fn)
{
    // https://github.com/swoole/swoole-src/issues/600
    // swoole_event_defer 在没有 IO 时会卡住 !!!
    // return swoole_event_defer($fn);
    return swoole_timer_after(1, $fn);
}

function new_($className, ...$args)
{
    assert(class_exists($className));

    if (method_exists($className, "__construct")) {
        $ctor = new \ReflectionMethod($className, "__construct");
        assert($ctor->isPublic());

        if ($ctor->isGenerator()) {
            $clazz = new \ReflectionClass($className);
            $obj = $clazz->newInstanceWithoutConstructor();
            $ignoredRet = (yield $ctor->invoke($obj, ...$args));
            // 这里需要特殊处理async
            if ($obj instanceof Async) {
                yield [$obj];
            } else {
                yield $obj;
            }
            return;
        }
    }

    yield new $className(...$args);
}

/**
 * @deprecated 对于swoole寥寥几个回调api没什么卵用
 * @param callable $fun
 * @param array ...$args
 * @return \Closure
 *
 * @example
 *
 * // 修复callback参数位置
 * function dns($host, $cb) {
 *      swoole_async_dns_lookup($host, function($_, $ip) use($cb) {
 *          $cb($ip);
 *      });
 * }
 *
 * spawn(function() {
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