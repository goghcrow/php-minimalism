<?php
namespace _;

class Gen
{
    public $isfirst = true;
    public $generator;

    public function __construct(\Generator $generator)
    {
        $this->generator = $generator;
    }

    public function valid()
    {
        return $this->generator->valid();
    }

    public function send($value = null)
    {
        if ($this->isfirst) {
            $this->isfirst = false;
            return $this->generator->current();
        } else {
            return $this->generator->send($value);
        }
    }

    // PHP7 之前 关键词不能用作名字
    public function throw_(\Exception $ex)
    {
        return $this->generator->throw($ex);
    }
}

/*
final class AsyncTask
{
    public $gen;

    public function __construct(\Generator $gen)
    {
        $this->gen = new Gen($gen);
    }

    public function begin()
    {
        return $this->next();
    }

    public function next($result = null, \Exception $ex = null)
    {
        try {
            if ($ex) {
                // c. 直接抛出异常
                // $ex来自子生成器, 调用父生成器throw抛出
                // 这里实现了 try { yield \Generator; } catch(\Exception $ex) { }
                echo "c -> ";
                $value = $this->gen->throw_($ex);
            } else {
                // a2. 当前生成器可能抛出异常
                echo "a2 -> ";
                $value = $this->gen->send($result);
            }

            if ($this->gen->valid()) {
                if ($value instanceof \Generator) {
                    // a3. 子生成器可能抛出异常
                    echo "a3 -> ";
                    $value = (new self($value))->begin();
                }
                echo "a4 -> ";
                return $this->next($value);
            } else {
                return $result;
            }
        } catch (\Exception $ex) {
            if (!isset($ex->gen_hash)) {
                $ex->gen_hash = spl_object_hash($this->gen);
            }
            // !! 当生成器迭代过程发生未捕获异常, 生成器将会被关闭, valid()返回false,
            if ($this->gen->valid()) {
                // b1.
                // 所以, 当前分支的异常一定不是当前生成器所抛出, 而是来自嵌套的子生成器
                // 此处将子生成器异常通过(c)向当前生成器抛出异常
                echo "b1 -> ";
                return $this->next(null, $ex);
            } else {
                // b2.
                // 逆向(递归栈)方向向上抛 或者
                // 向父生成器(如果存在)抛出异常
                echo "b2 -> ";
                throw $ex;
            }
        }
    }
}
*/


interface Async
{
    public function begin(callable $continuation = null);
}

final class AsyncTask implements Async
{
    public $parent;
    public $gen;
    public $continuation;

    public function __construct(\Generator $gen, AsyncTask $parent = null)
    {
        $this->gen = new Gen($gen);
        $this->parent = $parent;
    }

    public function begin(callable $continuation = null)
    {
        $this->continuation = $continuation;
        $this->next();
    }

    public function next($result = null, $ex = null)
    {
        try {
            if ($ex) {
                $value = $this->gen->throw_($ex);
            } else {
                $value = $this->gen->send($result);
            }

            if ($this->gen->valid()) {
//                if ($value instanceof Syscall) {
//                    $value = $value($this);
//                }

                if ($value instanceof \Generator) {
                    $value = new self($value, $this);
                }

                if ($value instanceof Async) {
                    $value->begin([$this, "next"]);
                } else {
                    $this->next($value, null);
                }
            } else {
                if ($continuation = $this->continuation) {
                    $continuation($result, null);
                }
            }
        } catch (\Exception $ex) {
            if ($this->gen->valid()) {
                $this->next(null, $ex);
            } else {
                if ($continuation = $this->continuation) {
                    $continuation(null, $ex);
                }
            }
        }
    }
}



// 当生成器迭代过程发生未捕获异常, 生成器将会被关闭, valid()返回false,
// 未捕获异常会从生成器内部被抛向父作用域,
// 嵌套子生成器内部的未捕获异常必须最终被抛向根生成器的calling frame
// PHP7 Generator的resume中异常采取goto try_again:标签方式层层向上抛出
// 我们的代码因为递归迭代的原因, 未捕获异常需要逆向(递归栈帧)方向层层上抛 , 性能方便有改进余地