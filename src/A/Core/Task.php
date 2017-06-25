<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/1/30
 * Time: 上午12:29
 */

namespace Minimalism\A\Core;

use Minimalism\A\Core\Exception\TaskCanceledException;


// TODO
// waitAll waitAny 超时处理
// AggregateException 处理
// continueWith task, Continuation Task ?!



/**
 * Class Task
 *
 * 半协程调度器
 *
 * @author xiaofeng
 *
 * 异步任务辅助类:
 * 通过yield实现控制流操控, 同步语法解糖(CPS转换?!)
 * 展开yield, 手动迭代\Generator, 通过$continuation传递异步任务结果
 *
 *
 * 说明:
 * 1. 可以通过then回调取回任务最终结果(result, exception)
 *    如果不想通过continuation回调获取result与exception, 可以多嵌套一层, 作为子任务运行,
 *    在父任务try catch异常, 获取结果, 忽略父任务continuation回调
 * 2. 不会因为exception导致fatal error，(swoole异步回调的内的异常必须捕获, 否则Fatal Error)，
 *      通过then回调continuation取回异常, 或者绑定全局$unObservedExceptionHandler
 * 2. continuation 中不能抛出异常 !!!
 * 3. 抛出 CancelTaskException 及其子类, 不在任务间透传, 直接终止异步任务(停止迭代), 执行continuation回调
 * 4. 抛出 其他异常 内部不捕获, 任务会终止, 异常通过continuation回调参数传递
 * 5. 抛出 其他异常 内部捕获, 任务继续执行
 * 6. Async 实现类内部通过回调函数参数传递执行结果与异常
 * 7. 递归实现, 避免在大循环内部同步yield, 会占用大量内存, 如果有Async返回则没问题, 回调函数之前会退栈
 */
final class Task implements Async
{
    /**
     * @var callable
     *      unObservedExceptionHandler :: function(Task $task, \Exception $ex) { }
     * 是否采用更复杂的EventEmitter方式实现 ?!
     */
    public static $unObservedExceptionHandler;

    private $isfirst = true;

    public $parent;
    public $generator;
    public $continuation;

    public $status;
    public $result;
    public $exception;

    /**
     * AsyncTask constructor.
     * @param \Generator $generator
     * @param Task|null $parent
     */
    public function __construct(\Generator $generator, Task $parent = null)
    {
        $this->generator = $generator;
        $this->parent = $parent;
        // $this->status = TaskStatus::Created;
        $this->setStatus(TaskStatus::Created);
    }

    /**
     * @param callable|null $continuation
     * continuation :: function($r = null, $ex = null) { }
     */
    public function start(callable $continuation = null)
    {
        $this->continuation = $continuation;
        // $this->status = TaskStatus::Running;
        $this->setStatus(TaskStatus::Running);
        $this->next();
    }

    /**
     * @param mixed|null $result
     * @param \Throwable|\Exception|null $ex
     * @internal
     */
    public function next($result = null, $ex = null)
    {
        if ($ex instanceof TaskCanceledException) {
            // $this->status = TaskStatus::Canceled;
            $this->setStatus(TaskStatus::Canceled);
            $this->orContinue(null, $ex);
            return;
        }

        try {
            if ($ex) {
                $value = $this->generator->throw($ex);
            } else {
                if ($this->isfirst) {
                    $this->isfirst = false;
                    $value = $this->generator->current();
                } else {
                    $value = $this->generator->send($result);
                }
            }

            if ($this->generator->valid()) {
                if ($value instanceof Syscall) {
                    $value = $value($this);
                }

                if ($value instanceof \Generator) {
                    // $this->status = TaskStatus::WaitingForChildrenToComplete;
                    $this->setStatus(TaskStatus::WaitingForChildrenToComplete);
                    $value = new static($value, $this);
                }

                if ($value instanceof Async) {
                    /** @var $value Async */
                    $value->start([$this, "next"]);
                } else {
                    $this->next($value, null);
                }
            } else {
                // $this->status = TaskStatus::RanToCompletion;
                $this->setStatus(TaskStatus::RanToCompletion);
                $this->orContinue($result, null);
            }
        } catch (\Exception $ex) {
            if ($this->generator->valid()) {
                $this->next(null, $ex);
            } else {
                // $this->status = TaskStatus::Faulted;
                $this->setStatus(TaskStatus::Faulted);
                $this->orContinue(null, $ex);
            }
        }
    }

    private function orContinue($result, $ex)
    {
        $this->result = $result;
        $this->exception = $ex;

        // 当发生异常, 有continuation, 交给continuation
        // 没有continuation, 有全局$unObservedExceptionHandler, 交给全局$unObservedExceptionHandler
        // 否则在析构函数抛出

        if ($continuation = $this->continuation) {
            $continuation($result, $ex);
        } else {
            $this->status = TaskStatus::WaitingForContinue;
        }
    }

    public function isCanceled()
    {
        return $this->status === TaskStatus::Canceled;
    }

    public function isCompleted()
    {
        return $this->status === TaskStatus::RanToCompletion;
    }

    public function isFaulted()
    {
        return $this->status === TaskStatus::Faulted;
    }

    public static function delay($ms)
    {
        return static::callcc(function($k) use($ms) {
            \swoole_timer_after($ms, function() use($k) {
                $k(null);
            });
        });
    }

//
//    /**
//     * Creates a Task that's completed due to cancellation with a specified cancellation token.
//     * @param CancellationToken $cancellationToken
//     * @return static
//     */
//    public static function fromCanceled(CancellationToken $cancellationToken)
//    {
//
//    }
//
//    /**
//     * Creates a Task that has completed with a specified exception.
//     * @param \Exception $exception
//     * @return static
//     */
//    public static function fromException(\Exception $exception)
//    {
//
//    }
//

    public static function wait($ms)
    {
        return static::callcc(function($k) use($ms) {

        });
    }

    public static function run($task, callable $continuation = null)
    {
        $task = Gen::from($task);
        $task = new static($task);
        $task->start($continuation);
    }

    /**
     * fork a future task
     * @param $task
     * @return Syscall
     */
    public static function fork($task)
    {
        $task = Gen::from($task);
        return new Syscall(function(Task $parent) use($task) {
            return new FutureTask($task, $parent);
        });
    }

    public static function chan($buffer = 0)
    {
        if ($buffer === 0) {
            return new Channel();
        } else {
            return new BufferChannel($buffer);
        }
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
     * @return Async 可以使用call/cc在async环境中将任务异步接口转换为同步接口
     *
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
     * functions/callcc 支持超时, 或者用race可以更干净的实现
     */
    public static function callcc(callable $fun)
    {
        return new CallCC($fun);
    }

    /**
     * 取消任务
     * @return Syscall
     */
    public static function cancel()
    {
        return new Syscall(function(/*AsyncTask $task*/) {
            throw new TaskCanceledException();
        });
    }

    /**
     * 跨父子AsyncTask获取上下文
     * @param string $key
     * @param mixed $default
     * @return Syscall
     */
    public static function getCtx($key, $default = null)
    {
        return new Syscall(function(Task $task) use($key, $default) {
            while($task->parent && $task = $task->parent);
            if (isset($task->generator->$key)) {
                return $task->generator->$key;
            } else {
                return $default;
            }
        });
    }

    /**
     * 跨父子AsyncTask设置上下文
     * @param string $key
     * @param mixed $val
     * @return Syscall
     */
    public static function setCtx($key, $val)
    {
        return new Syscall(function(Task $task) use($key, $val) {
            while($task->parent && $task = $task->parent);
            $task->generator->$key = $val;
        });
    }

    /**
     * Promise.all, parallel
     * @param array $tasks
     * @param int $ms TODO
     * @return Syscall
     *
     * @see http://blog.zhaojie.me/2012/04/exception-handling-in-csharp-async-await-2.html
     * WhenAll是一个辅助方法，它的输入是n个Task对象，输出则是个返回它们的结果数组的Task对象。
     * 新的Task对象会在所有输入全部“结束”后才完成。
     * 在这里“结束”的意思包括成功和失败（取消也是失败的一种，即抛出了OperationCanceledException）。
     * 换句话说，假如这n个输入中的某个Task对象很快便失败了，也必须等待其他所有输入对象成功或是失败之后，
     * 新的Task对象才算完成。而新的Task对象完成后又可能会有两种表现：
     *
     * 所有输入Task对象都成功了：则返回它们的结果数组。
     * 至少一个输入Task对象失败了：则抛出“其中一个”异常。
     * 全部成功的情况自不必说，那么在失败的情况下，什么叫做抛出“其中一个”异常？如果我们要处理所有抛出的异常该怎么办？
     *
     */
    public static function whenAll(array $tasks, $ms = 0)
    {
        $tasks = array_map([Gen::class, "from"], $tasks);

        return new Syscall(function(Task $parent) use($tasks) {
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
     * @param int $ms TODO
     * @return Syscall
     */
    public static function whenAny(array $tasks, $ms = 0)
    {
        $tasks = array_map([Gen::class, "from"], $tasks);

        return new Syscall(function(Task $parent) use($tasks) {
            if (empty($tasks)) {
                return null;
            } else {
                return new Any($tasks, $parent);
            }
        });
    }

    public function __destruct()
    {
        $hasUnObservedEx =
            $this->status === TaskStatus::WaitingForContinue
            && $this->exception
            && !($this->exception instanceof TaskCanceledException);

        if ($hasUnObservedEx) {
            if ($exHandler = self::$unObservedExceptionHandler) {
                $exHandler($this, $this->exception);
            } else {
                // 如果Task没有continuation, 且发生异常, 则在对象析构时抛出
                // 如果异步的话, 会发生Fatal error, 进程退出 !!!
                // 强迫必须处理每一个Task对象的异常
                throw $this->exception;
            }

        }
    }

    /**
     * for DEBUG trace status
     * @param int $status
     */
    private function setStatus($status)
    {
        $this->status = $status;

        $hash = spl_object_hash($this);
        $name = TaskStatus::getName($status);
        // echo "task [$hash] -> $name\n"; // TODO
    }
}

/*
 * TODO
 *
 *
 * Task Parallel Library (TPL)
 * https://msdn.microsoft.com/en-us/library/dd460717(v=vs.110).aspx
 *
 * System.Threading.Tasks Namespace
 * https://msdn.microsoft.com/en-us/library/system.threading.tasks(v=vs.110).aspx
 *
 * Task-based Asynchronous Programming
 * https://msdn.microsoft.com/en-us/library/dd537609(v=vs.110).aspx
 *
 * 关于C#中async/await中的异常处理（上）
 * http://blog.zhaojie.me/2012/04/exception-handling-in-csharp-async-await-1.html
 *
 * 关于C#中async/await中的异常处理（下）
 * http://blog.zhaojie.me/2012/04/exception-handling-in-csharp-async-await-2.html
 *
 * Asynchronous Programming with async and await (C#)
 * https://msdn.microsoft.com/en-us/library/mt674882.aspx
 *
 * Async/Await - Best Practices in Asynchronous Programming
 * https://msdn.microsoft.com/en-us/magazine/jj991977.aspx
 *
 *
 *
 * Chaining Tasks by Using Continuation Tasks
 * https://msdn.microsoft.com/en-us/library/ee372288(v=vs.110).aspx
 *
 * Attached and Detached Child Tasks
 * https://msdn.microsoft.com/en-us/library/dd997417(v=vs.110).aspx
 *
 * Task Cancellation
 * https://msdn.microsoft.com/en-us/library/dd997396(v=vs.110).aspx
 *
 * Exception Handling
 * https://msdn.microsoft.com/en-us/library/dd997415(v=vs.110).aspx
 *
 * How to: Use Parallel.Invoke to Execute Parallel Operations
 * https://msdn.microsoft.com/en-us/library/dd460705(v=vs.110).aspx
 *
 * How to: Return a Value from a Task
 * https://msdn.microsoft.com/en-us/library/dd537613(v=vs.110).aspx
 *
 * How to: Cancel a Task and Its Children
 * https://msdn.microsoft.com/en-us/library/dd537607(v=vs.110).aspx
 *
 * How to: Create Pre-Computed Tasks
 * https://msdn.microsoft.com/en-us/library/hh228607(v=vs.110).aspx
 *
 * How to: Traverse a Binary Tree with Parallel Tasks
 * https://msdn.microsoft.com/en-us/library/dd557750(v=vs.110).aspx
 *
 * How to: Unwrap a Nested Task
 * https://msdn.microsoft.com/en-us/library/ee795275(v=vs.110).aspx
 *
 * How to: Prevent a Child Task from Attaching to its Parent
 * https://msdn.microsoft.com/en-us/library/hh228608(v=vs.110).aspx
 *
 */