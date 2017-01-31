<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/1/30
 * Time: 上午12:29
 */

namespace Minimalism\Async\Core;


/**
 * Class AsyncTask
 *
 * @author xiaofeng
 *
 * 手动迭代\Generator, 通过yield操纵控制流, 完成从yield到CPS转换的异步任务辅助类(解糖)
 *
 * 说明:
 * 1. 可以通过start回调取回任务最终结果(result, exception)
 * 2. 不会因为exception导致fatal error， 通过start回调complete取回异常
 * 3. 抛出 CancelTaskException 及其子类 终止异步任务(停止迭代),[待定：complete回调不会执行, 不在任务间透传]
 * 4. 抛出 其他异常 内部不捕获, 任务会终止, 异常通过complete回调参数传递
 * 5. 抛出 其他异常 内部捕获, 任务继续执行
 * 6. IAsync 实现类内部通过回调函数参数传递执行结果与异常
 * 7. 递归实现, 注意stack内存占用, Maximum function nesting level 限制, 避免注意循环 yield
 */
final class AsyncTask implements IAsync
{
    private $generator;
    private $complete;

    public function __construct(\Generator $generator)
    {
        $this->generator = new Generator($generator);
    }

    /**
     * @param callable|null $complete
     * 任务完成回调 complete: function($r, $ex) { }
     * @return void
     */
    public function start(callable $complete = null)
    {
        $this->complete = $complete;
        $this->next();
    }

    /**
     * 从\Generator迭代器获取<异步任务(Async)>或者<yield value>,
     * 如果是<异步任务(Async)>则启动,并且在异步任务完成时候, 将返回值或异常作为参数, 调用自身
     * 如果是<yield value>, 则直接将value作为参数(result), 调用自身
     * 如果期间捕获到非CancelTaskException异常, 则将exception作为参数(exception), 调用自身
     * 如果期间捕获到CancelTaskException异常, 迭代器停止, 是否call complete待定
     *
     * @param mixed|null $result
     * @param \Exception|null $ex
     * @internal
     */
    public function next($result = null, \Exception $ex = null)
    {
        // 无条件终止迭代,实现通过异常终止Task
        if ($ex instanceof CancelTaskException) {
            // goto complete; // 斟酌一下取消任务是否需要call complete回调
            return;
        }

        try {
            if (!$this->generator->valid()) {
                goto complete;
            }

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

                // 返回新迭代器,需要将\Generator 转换为异步任务 (Async)
                if ($value instanceof \Generator) {
                    $value = new self($value);
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
                complete:
                // \Generator 迭代完成
                if ($cb = $this->complete) {
                    // 传递 嵌套异步任务的返回值与异常
                    $cb($result, $ex);
                }
            }
        } catch (\Exception $ex) {
            $this->next(null, $ex);
        }
    }
}