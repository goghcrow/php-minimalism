<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/1/30
 * Time: 上午12:29
 */

namespace Minimalism\A\Core\V1;


/**
 * Class Generator
 *
 * FIX send method
 *
 * @package Minimalism\A\Core
 */
class Generator
{
    public $gen;
    public $isfirst = true;

    public function __construct(\Generator $g)
    {
        $this->gen = $g;
    }

    public function valid()
    {
        return $this->gen->valid();
    }

    public function send($value = null)
    {
        if ($this->isfirst) {
            $this->isfirst = false;
            return $this->gen->current();
        } else {
            return $this->gen->send($value);
        }
    }

    // throw: keywords can be used as name in php7 only
    public function throwex(\Exception $ex)
    {
        return $this->gen->throw($ex);
    }
}


/**
 * CPS: Interface IAsync
 * @package Minimalism\A\Core
 */
interface IAsync
{
    /**
     * 开启异步任务，立即返回，任务完成回调$continuation
     * @param callable $continuation
     * @return void
     */
    public function start(callable $continuation);
}


class CancelTaskException extends \Exception { }


class Syscall
{
    private $fun = null;

    public function __construct(callable $fun)
    {
        $this->fun = $fun;
    }

    public function __invoke(AsyncTask $task)
    {
        $cb = $this->fun;
        return $cb($task);
    }
}

/**
 * Class AsyncTask
 *
 * @author xiaofeng
 *
 * 异步任务辅助类:
 * 通过yield实现控制流操控, 同步语法解糖(CPS转换?!)
 * 展开yield, 手动迭代\Generator, 通过$continuation传递异步任务结果
 *
 *
 * 说明:
 * 1. 可以通过start回调取回任务最终结果(result, exception)
 *    如果不想通过continuation回调获取result与exception, 可以多嵌套一层, 作为子任务运行,
 *    在父任务try catch异常, 获取结果, 忽略父任务continuation回调
 * 2. 不会因为exception导致fatal error，(swoole异步回调的内的异常必须捕获, 否则Fatal Error)， 通过start回调continuation取回异常
 * 3. 抛出 CancelTaskException 及其子类, 不在任务间透传, 直接终止异步任务(停止迭代), 执行continuation回调
 * 4. 抛出 其他异常 内部不捕获, 任务会终止, 异常通过continuation回调参数传递
 * 5. 抛出 其他异常 内部捕获, 任务继续执行
 * 6. IAsync 实现类内部通过回调函数参数传递执行结果与异常
 * 7. 递归实现, 避免循环yield, 会占用大量内存
 *    其实 \SplStack 实现也无法避免该问题, 某些情况 \SplStack 同样会膨胀
 *    因为 stack 都在堆上~ 最后都会导致 Fatal error: Allowed memory size ...
 */
final class AsyncTask implements IAsync
{
    public $generator;

    public $continuation;
    public $parent;

    public function __construct(\Generator $generator, AsyncTask $parent = null)
    {
        $this->generator = new Generator($generator);
        $this->parent = $parent;
    }

    /**
     * @param callable|null $continuation
     * 任务完成回调 complete: function($r, $ex) { }
     * @return void
     */
    public function start(callable $continuation = null)
    {
        $this->continuation = $continuation;
        $this->next();
    }

    public function __invoke(callable $complete = null)
    {
        $this->start($complete);
    }

    /**
     * 从\Generator迭代器获取<异步任务(Async)>或者<yield value>,
     * 如果是<异步任务(Async)>则启动,并且在异步任务完成时候, 将返回值或异常作为参数, 调用自身
     * 如果是<yield value>, 则直接将value作为参数(result), 调用自身
     * 如果期间捕获到非CancelTaskException异常, 则将exception作为参数(exception), 调用自身
     * 如果期间捕获到CancelTaskException异常, 迭代器停止, call continuation
     *
     * @param mixed|null $result
     * @param \Exception|null $ex
     * @internal
     */
    public function next($result = null, \Exception $ex = null)
    {
        // CancelTaskException 无条件终止迭代,实现通过异常终止Task
        if ($ex instanceof CancelTaskException || !$this->generator->valid()) {
            goto continuation;
        }

        try {
            // step1 控制迭代器

            // 从迭代器获取 1.异步任务 或者 2.primeValue, 即yield 右值 $value
            // 优先处理异常, 有ex则忽略result
            if ($ex) {
                $value = $this->generator->throwex($ex);
            } else {
                // 传递 yield `左值`r 并进行迭代: r = (yield)
                $value = $this->generator->send($result);
            }

            // step2 延续执行

            // valid() === true 时, $value 才有效
            if ($this->generator->valid()) {

                if ($value instanceof Syscall) {
                    $value = $value($this);
                }

                // 返回新迭代器,需要将\Generator 转换为异步任务 (Async)
                if ($value instanceof \Generator) {
                    $value = new self($value, $this);
                }

                // 从迭代器获取到了一个异步任务
                if ($value instanceof IAsync) {
                    // [$this, "next"] 为异步任务Async 的 Continuation
                    $value->start([$this, "next"]);
                } else {
                    // 传递 yield `右值` value : yield value;
                    // yieldValue 转换为 result
                    $this->next($value, null);
                }
            } else {

                continuation:
                if ($continuation = $this->continuation) {
                    // 传递 嵌套异步任务的返回值与异常
                    $continuation($result, $ex);
                }
            }
        } catch (\Exception $ex) {
            $this->next(null, $ex);
        }
    }
}


class CallCC implements IAsync
{
    public $fun;

    public function __construct(callable $fun)
    {
        $this->fun = $fun;
    }

    public function start(callable $continuation)
    {
        $fun = $this->fun;

        // 不处理返回值，user-func返回值通过延续进行传递
        $fun($continuation);
    }
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
 * @return
 *
 * 可以使用call/cc在async环境中将任务异步接口转换为同步接口
 */
/*
function callcc(callable $fun)
{
    return new CallCC($fun);
}
*/


// 可以使用call/cc轻松转换异步接口
// yield asyncSleep
/*
function asyncSleep($ms)
{
    return new CallCC(function($k) use($ms) {
        swoole_timer_after($ms, function() use($k) {
            $k();
        });
    });
}

$result = (yield callcc(function($k) {
    doSomethingAsync(function($result) use($k) {
        // 通过延续把异步结果返回给yield表达式左值
        $k($result);
    });
}));
*/