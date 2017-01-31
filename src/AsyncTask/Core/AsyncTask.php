<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/1/30
 * Time: 上午12:29
 */

namespace Minimalism\AsyncTask\Core;


/**
 * Class AsyncTask
 *
 * CPS continuation passing style
 * 脱糖直接控制，迭代器执行
 *
 * 1. 可以通过start回调取回任务最终结果
 * 2. 不会因为exception导致fatal error， 通过start回调取回异常
 * 3. CancelTaskException 及其子类 不在任务间透传
 * 4. 抛出 CancelTaskException 及其子类 终止异步任务，任务不会被继续调度，complete回调不会执行
 * 5. 抛出 其他异常 内部不捕获， 任务会终止，异常通过complete回调参数传递
 * 6. 抛出 其他异常 内部捕获， 任务继续执行
 * 7. Async 实现类通过回调传递执行结果与异常
 */
class AsyncTask implements Async
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

    public function next($result = null, \Exception $ex = null)
    {
        if ($ex instanceof CancelTaskException) {
            return;
        }

        try {
            if (!$this->generator->valid()) {
                goto complete;
            }

            // 优先处理异常, 有ex则忽略result
            if ($ex) {
                $value = $this->generator->throwex($ex);
            } else {
                // 传递 yield `左值`r 并进行迭代: r = (yield)
                $value = $this->generator->send($result);
            }

            if ($this->generator->valid()) {
                if ($value instanceof \Generator) {
                    $value = new self($value); // To Async
                }

                if ($value instanceof Async) {
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
                    // 传递 嵌套\Generator的返回值与异常
                    $cb($result, $ex);
                }
            }
        } catch (\Exception $ex) {
            $this->next(null, $ex);
        }
    }
}