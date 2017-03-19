<?php

namespace _;
// TODO 省略未修改方法


// 统一\Generator接口, 屏蔽send会直接跳到第二次yield的问题
// http://php.net/manual/en/generator.send.php 参见send方法说明
// 内部隐式rewind, 需要先调用current() 获取当前value

/*
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
}
*/

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

// 先让迭代器跑起来, 并且将yield值send为yield 表达式结果
// send 为之后yield 异步表达式做好铺垫
// 我们需要递归的执行next 将当前yield值作为yield表达式结果, 直到迭代器终止
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
        $this->next();
    }

    public function next($result = null)
    {
        $value = $this->gen->send($result);
        if ($this->gen->valid()) {
            $this->next($value);
        }
    }
}

function newGen()
{
    $r1 = (yield 1);
    $r2 = (yield 2);
    echo $r1, $r2;
}
$task = new AsyncTask(newGen());
$task->begin(); // output: 12
*/

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

// 2. PHP7 支持 Generator::getReturn,可以通过return 返回值
// 在PHP5 支持获取Generator最后一次yield值作为Generator的返回值
// 让 \Generator可以返回值, 为之后yield 异步调用返回值做好铺垫
// return传递每一次迭代的结果最终到begin方法
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

    public function next($result = null)
    {
        $value = $this->gen->send($result);

        if ($this->gen->valid()) {
            return $this->next($value);
        } else {
            return $result;
        }
    }
}

function newGen()
{
    $r1 = (yield 1);
    $r2 = (yield 2);
    echo $r1, $r2;
    yield 3;
}
$task = new AsyncTask(newGen());
$r = $task->begin(); // output: 12
echo $r; // output: 3
*/

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

// PHP7 \Generator支持delegation, 可以自动展开yield antherGenerator
// 我们需要在PHP5支持嵌套子生成器, 且支持将子生成器最后yield值作为yield表达式结果send回父生成器
// 我们也只需要加两行代码, 递归的产生一个AsyncTask对象来执行子生成器即可
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

    public function next($result = null)
    {
        $value = $this->gen->send($result);

        if ($this->gen->valid()) {
            if ($value instanceof \Generator) {
                $value = (new self($value))->begin();
            }
            return $this->next($value);

        } else {
            return $result;
        }
    }
}

function newSubGen()
{
    yield 0;
    yield 1;
}

function newGen()
{
    $r1 = (yield newSubGen());
    $r2 = (yield 2);
    echo $r1, $r2;
    yield 3;
}
$task = new AsyncTask(newGen());
$r = $task->begin(); // output: 12
echo $r; // output: 3
*/

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

// 4. 异步化,
// return其实可以被看成单参数, 且永远不会返回的函数, return :: r -> void
// 将return 改写为 函数参数continuation
// 干掉 return, CPS变换, \Generator结果通过回调形式返回
// 为引入异步迭代做准备
/*
final class AsyncTask
{
    public $gen;
    public $continuation;

    public function __construct(\Generator $gen)
    {
        $this->gen = new Gen($gen);
    }

    public function begin(callable $continuation)
    {
        $this->continuation = $continuation;
        $this->next();
    }

    public function next($result = null)
    {
        $value = $this->gen->send($result);

        if ($this->gen->valid()) {
            if ($value instanceof \Generator) {
                // 父任务next方法是子任务的延续,
                // 子任务迭代完成后继续完成父任务迭代
                $continuation = [$this, "next"];
                (new self($value))->begin($continuation);
            } else {
                $this->next($value);
            }

        } else {
            $cc = $this->continuation;
            $cc($result);
        }
    }
}

function newSubGen()
{
    yield 0;
    yield 1;
}

function newGen()
{
    $r1 = (yield newSubGen());
    $r2 = (yield 2);
    echo $r1, $r2;
    yield 3;
}
$task = new AsyncTask(newGen());

$trace = function($r) { echo $r; };
$task->begin($trace); // output: 12
*/

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=


// 引入 抽象的异步接口
/*
// 个人愚见, 只有一个方法的接口通常都可以使用闭包代替
// 这里仍然需要一个接口是因为引入一个新类型方便区分
interface Async
{
    public function begin(callable $callback);
}

// AsyncTask 自身经CPS变换, 符合Async定义, 显示实现该Async
final class AsyncTask implements Async
{
    public $gen;
    public $continuation;

    public function __construct(\Generator $gen)
    {
        $this->gen = new Gen($gen);
    }

    public function begin(callable $continuation)
    {
        $this->continuation = $continuation;
        $this->next();
    }

    public function next($result = null)
    {
        $value = $this->gen->send($result);

        if ($this->gen->valid()) {
            if ($value instanceof \Generator) {
                $value = new self($value);
            }

            if ($value instanceof Async) {
                $async = $value;
                $continuation = [$this, "next"];
                $async->begin($continuation);
            } else {
                $this->next($value);
            }

        } else {
            $cc = $this->continuation;
            $cc($result);
        }
    }
}


// 实现两个简单的例子
// 定时器修改为标准异步接口
class AsyncSleep implements Async
{
    public function begin(callable $cc)
    {
        swoole_timer_after(1000, $cc);
    }
}

// 异步dns查询修改为标准异步接口
class AsyncDns implements Async
{
    public function begin(callable $cc)
    {
        swoole_async_dns_lookup("www.baidu.com", function($host, $ip) use($cc) {
            // 这里其实符合callcc的语义, 通过调用$cc将返回值作为参数传入
            // $ip 通过$cc 从子生成器传入父生成器, 最终通过send方法成为yield表达式结果
            $cc($ip);
        });
    }
}

function newSubGen()
{
    yield 0;
    yield 1;
}

function newGen()
{
    $r1 = (yield newSubGen());
    $r2 = (yield 2);
    $start = time();
    yield new AsyncSleep();
    echo time() - $start, "\n";
    $ip = (yield new AsyncDns());
    yield "IP: $ip";
}
$task = new AsyncTask(newGen());

$trace = function($r) { echo $r; };
$task->begin($trace);
// output:
// 1
// IP: 115.239.210.27
*/


// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

// 以上已经完成了一个异步迭代器自动执行器，下面引入稍显繁琐的异常处理

// 为统一接口的Gen引入throw方法
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


// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// 引入异常处理

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
        if ($ex) {
            $this->gen->throw_($ex);
        }

        $ex = null;
        try {
            $value = $this->gen->send($result);
        } catch (\Exception $ex) {}

        if ($ex) {
            if ($this->gen->valid()) {
                return $this->next(null, $ex);
            } else {
                throw $ex;
            }
        } else {
            if ($this->gen->valid()) {
                return $this->next($value);
            } else {
                return $result;
            }
        }
    }
}

function newGen()
{
    $r1 = (yield 1);
    throw new \Exception("e");
    $r2 = (yield 2);
    yield 3;
}
$task = new AsyncTask(newGen());
try {
    $r = $task->begin();
    echo $r;
} catch (\Exception $ex) {
    echo $ex->getMessage(); // output: e
}
//*/

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

// 重新处理生成器嵌套, 需要将子生成器异常抛向父生成器

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
                $value = $this->gen->throw_($ex);
            } else {
                $value = $this->gen->send($result);
            }

            if ($this->gen->valid()) {
                if ($value instanceof \Generator) {
                    $value = (new self($value))->begin();
                }
                return $this->next($value);
            } else {
                return $result;
            }
        } catch (\Exception $ex) {
            if ($this->gen->valid()) {
                return $this->next(null, $ex);
            } else {
                throw $ex;
            }
        }
    }
}


function newSubGen()
{
    yield 0;
    throw new \Exception("e");
    yield 1;
}

function newGen()
{
    try {
        $r1 = (yield newSubGen());
    } catch (\Exception $ex) {
        echo $ex->getMessage();
    }
    $r2 = (yield 2);
    yield 3;
}
$task = new AsyncTask(newGen());
$r = $task->begin(); // output: e
echo $r; // output: 3
//*/


// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

// 重新 CPS

/*
final class AsyncTask
{
    public $gen;
    public $continuation;

    public function __construct(\Generator $gen)
    {
        $this->gen = new Gen($gen);
    }

    public function begin(callable $continuation)
    {
        $this->continuation = $continuation;
        $this->next();
    }

    public function next($result = null, \Exception $ex = null)
    {
        try {
            if ($ex) {
                $value = $this->gen->throw_($ex);
            } else {
                $value = $this->gen->send($result);
            }

            if ($this->gen->valid()) {
                if ($value instanceof \Generator) {
                    $continuation = [$this, "next"];
                    (new self($value))->begin($continuation);
                } else {
                    $this->next($value);
                }
            } else {
                // 迭代结束 返回结果
                $cc = $this->continuation; // 父任务next或continuation
                $cc($result, null);
            }
        } catch (\Exception $ex) {
            if ($this->gen->valid()) {
                // 抛出异常
                $this->next(null, $ex);
            } else {
                // 未捕获异常
                $cc = $this->continuation; // 父任务next或continuation
                $cc(null, $ex);
            }
        }
    }
}


function tt()
{
    yield;
    throw new \Exception("e");
}
function t()
{
    yield tt();
    yield 1;
}

$task = new AsyncTask(t());
$trace = function($r, $ex) {
    if ($ex) {
        echo $ex->getMessage(); // output: e
    } else {
        echo $r;
    }
};
$task->begin($trace);



function newSubGen()
{
    yield 0;
    throw new \Exception("e");
    yield 1;
}

function newGen()
{
    try {
        $r1 = (yield newSubGen());
    } catch (\Exception $ex) {
        echo $ex->getMessage(); // output: e
    }
    $r2 = (yield 2);
    yield 3;
}
$task = new AsyncTask(newGen());
$trace = function($r, $ex) {
    if ($ex) {
        echo $ex->getMessage();
    } else {
        echo $r; // output: 3
    }
};
$task->begin($trace); // output: e
//*/



// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

// 重新加入 Async, 修改continuation的签名  continuation:: (mixed r, \Exception ex) -> void

interface Async
{
    public function begin(callable $continuation);
}

/*
final class AsyncTask implements Async
{
    public $gen;

    public $continuation;

    public function __construct(\Generator $gen)
    {
        $this->gen = new Gen($gen);
    }

    public function begin(callable $continuation)
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
                if ($value instanceof \Generator) {
                    $value = new self($value);
                }

                if ($value instanceof Async) {
                    $cc = [$this, "next"];
                    $value->begin($cc);
                } else {
                    $this->next($value, null);
                }
            } else {
                $cc = $this->continuation;
                $cc($result, null);
            }
        } catch (\Exception $ex) {
            if ($this->gen->valid()) {
                $this->next(null, $ex);
            } else {
                $cc = $this->continuation;
                $cc($result, $ex);
            }
        }
    }
}
*/

$trace = function($r, $ex) {
    if ($ex instanceof \Exception) {
        echo "cc_ex:" . $ex->getMessage(), "\n";
    }
};


/*
class AsyncException implements Async
{
    public function begin(callable $cc)
    {
        swoole_timer_after(1000, function() use($cc) {
            $cc(null, new \Exception("timeout"));
        });
    }
}


function newSubGen()
{
    yield 0;
    yield new AsyncException();
}

function newGen($try)
{
    $start = time();
    try {
        $r1 = (yield newSubGen());
    } catch (\Exception $ex) {
        if ($try) {
            echo "catch:" . $ex->getMessage(), "\n";
        } else {
            throw $ex;
        }
    }
    echo time() - $start, "\n";
}



$task = new AsyncTask(newGen(true));
$task->begin($trace);
// output:
// catch:timeout
// 1

$task = new AsyncTask(newGen(false));
$task->begin($trace);
// output:
// cc_ex:timeout
*/


//function nestedTask($i) {
//    if ($i > 80) {
//        throw new \Exception($i);
//    } else {
//        yield nestedTask(++$i);
//    }
//}
//$task = new AsyncTask(nestedTask(0));
//$task->begin($trace);

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

// 引入CancelTaskException


// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

// 当出现嵌套的生成器时，从外部来看，其实只要一个任务，如果我们需要在生成器之间不通过参数共享数据，即上下文
// 则我们需要把父子生成器关联起来，方便进行回溯

// 引入parentTask
final class AsyncTask implements Async
{
    public $gen;

    public $continuation;

    public $parent;

    public function __construct(\Generator $gen, AsyncTask $parent = null)
    {
        $this->gen = new Gen($gen);
        $this->parent = $parent;
    }

    public function begin(callable $continuation)
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
                // 这里注意优先级, Syscall 可能返回\Generator 或者 Async
                if ($value instanceof Syscall) {
                    $value = $value($this);
                }

                if ($value instanceof \Generator) {
                    $value = new self($value, $this);
                }

                if ($value instanceof Async) {
                    $cc = [$this, "next"];
                    $value->begin($cc);
                } else {
                    $this->next($value, null);
                }
            } else {
                $cc = $this->continuation;
                $cc($result, null);
            }
        } catch (\Exception $ex) {
            if ($this->gen->valid()) {
                $this->next(null, $ex);
            } else {
                $cc = $this->continuation;
                $cc($result, $ex);
            }
        }
    }
}



// 引入与调度器内部交互的SysCall
// syscall :: AsyncTask $task -> mixed
// 将需要执行的函数打包成Syscall, 当我们通过yield返回迭代器时,
// 我们将会从函数参数获取到当前迭代器对象, 这大大方便了我们对于一些常见功能的封装
// 比如在嵌套迭代器之间共享数据Context

class Syscall
{
    private $fun;

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



// function g() { yield; }
// (new \ReflectionObject(g()))->getProperties();

function getCtx($key, $default = null)
{
    // 生成器对象本身没有任何属性，我们把context kv数据附加到父级生成器
    return new Syscall(function(AsyncTask $task) use($key, $default) {
        while($task->parent && $task = $task->parent);
        if (isset($task->gen->generator->$key)) {
            return $task->gen->generator->$key;
        } else {
            return $default;
        }
    });
}



function setCtx($key, $val)
{
    return new Syscall(function(AsyncTask $task) use($key, $val) {
        while($task->parent && $task = $task->parent);
        $task->gen->generator->$key = $val;
    });
}


function setTask()
{
    yield setCtx("foo", "bar");
}

function ctxTest()
{
    yield setTask();
    $foo = (yield getCtx("foo"));
    echo $foo;
}

$task = new AsyncTask(ctxTest());
$task->begin($trace);


// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

// 最终版本 !!!!!!

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

// 对AsyncTask简单封装

function async()
{
    $n = func_num_args();
    if ($n === 0) {
        return;
    }

    $task = func_get_arg(0);
    $continuation = function() {};
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
        }
    }

    if (is_callable($task)) {
        try {
            $task = $task();
        } catch (\Exception $ex) {
            $continuation(null, $ex);
            return;
        }
    }

    if ($task instanceof \Generator) {
        foreach ($ctx as $k => $v) {
            $task->$k = $v;
        }
        (new AsyncTask($task, $parent))->begin($continuation);
    } else {
        $continuation($task, null);
    }
}

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

// 到时间做一些好玩的事情了
// 之前我们需要把异步回调风格的函数转换为生成器内部可直接调用
// 需要显示实现Async接口
// 当我们发现返回值是通过函数参数返回给调度器时，会惊奇的发现这个模式与call/cc很像
// 我们把这个简单的模式抽象出来，实现一个穷人的call/cc

class CallCC implements Async
{
    public $fun;

    public function __construct(callable $fun)
    {
        $this->fun = $fun;
    }

    public function begin(callable $continuation)
    {
        $fun = $this->fun;
        $fun($continuation);
    }
}

//function callcc(callable $fn)
//{
//    return new CallCC($fn);
//}

// 让我们看看callcc可以用来做什么

// 用来做cps变换, 实质上是将
// asyncXXX(...args, callback) : void => syncXXX(...args) Async
// Async begin(callback) : void

// asyncInvoke :: (a, b -> void) -> void
// syncInvoke :: a -> (b -> void)

// asyncInvoke(...args, (fn(T) : void)) : void
// syncInvoke(...args) : (fn(T): void)


//
function async_sleep($ms)
{
    return callcc(function($k) use($ms) {
        swoole_timer_after($ms, function() use($k) {
            $k(null);
        });
    });
}

//function async_dns_lookup($host)
//{
//    return callcc(function($k) use($host) {
//        swoole_async_dns_lookup($host, function($host, $ip) use($k) {
//            $k($ip);
//        });
//    });
//}

//class HttpClient extends \swoole_http_client
//{
//    public function awaitGet($uri)
//    {
//        return callcc(function($k) use($uri) {
//            $this->get($uri, $k);
//        });
//    }
//
//    public function awaitPost($uri, $post)
//    {
//        return callcc(function($k) use($uri, $post) {
//            $this->post($uri, $post, $k);
//        });
//    }
//
//    public function awaitExecute($uri)
//    {
//        return callcc(function($k) use($uri) {
//            $this->execute($uri, $k);
//        });
//    }
//}


async(function() {
    $ip = (yield async_dns_lookup("www.baidu.com"));
    $cli = new HttpClient($ip, 80);
    $cli->setHeaders(["foo" => "bar"]);
    $cli = (yield $cli->awaitGet("/"));
    echo $cli->body, "\n";
});

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

// 你可能已经注意到我们上面接口的问题了，没有任何超时处理
// 通常情况我们需要为每个异步添加定时器, 回调成功取消定时器, 否则在定时器回调


function once(callable $fun)
{
    $has = false;
    return function(...$args) use($fun, &$has) {
        if ($has === false) {
            $fun(...$args);
            $has = true;
        }
    };
}

function timeoutWrapper(callable $fun, $timeout)
{
    return function($k) use($fun, $timeout) {
        $k = once($k);
        $fun($k);
        swoole_timer_after($timeout, function() use($k) {
            $k(null, new \Exception("timeoutWrapper"));
        });
    };
}

// 为callcc添加超时处理
function callcc(callable $fun, $timeout = 0)
{
    if ($timeout > 0) {
        $fun = timeoutWrapper($fun, $timeout);
    }
    return new CallCC($fun);
}


//function async_dns_lookup($host, $timeout = 100)
//{
//    return callcc(function($k) use($host) {
//        swoole_async_dns_lookup($host, function($host, $ip) use($k) {
//            $k($ip);
//        });
//    }, $timeout);
//}
//
//
//async(function() {
//    try {
//        yield async_dns_lookup("www.xxx.com", 1);
//    } catch (\Exception $ex) {
//        echo $ex;
//    }
//});


// 我们有更优雅的方式来实现超时

class Any implements Async
{
    public $parent;
    public $tasks;
    public $continuation;
    public $done;

    public function __construct(array $tasks, AsyncTask $parent = null)
    {
        assert(!empty($tasks));
        $this->tasks = $tasks;
        $this->parent = $parent;
        $this->done = false;
    }

    public function begin(callable $continuation)
    {
        $this->continuation = $continuation;
        foreach ($this->tasks as $id => $task) {
            (new AsyncTask($task, $this->parent))->begin($this->continuation($id));
        };
    }

    private function continuation($id)
    {
        return function($r, $ex = null) use($id) {
            if ($this->done) {
                return;
            }
            $this->done = true;

            if ($this->continuation) {
                $k = $this->continuation;
                $k($r, $ex);
            }
        };
    }
}


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


function race(array $tasks)
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


// 这样我们就实现了一个类似Promise.race的接口


// 我们拿回来这个简单dns查询函数
//function async_dns_lookup($host)
//{
//    return callcc(function($k) use($host) {
//        swoole_async_dns_lookup($host, function($host, $ip) use($k) {
//            $k($ip);
//        });
//    });
//}


function timeout($ms)
{
    return callcc(function($k) use($ms) {
        swoole_timer_after($ms, function() use($k) {
            $k(null, new \Exception("timeout"));
        });
    });
}

//async(function() {
//    try {
//        $ip = (yield race([
//            async_dns_lookup("www.baidu.com"),
//            timeout(100),
//        ]));
//
//        $res = (yield race([
//            (new HttpClient($ip, 80))->awaitGet("/"),
//            timeout(200),
//        ]));
//        var_dump($res->statusCode);
//    } catch (\Exception $ex) {
//        echo $ex;
//        swoole_event_exit();
//    }
//});


// 我们可以很容易构造出超时的接口

class HttpClient extends \swoole_http_client
{
    public function awaitGet($uri, $timeout = 1000)
    {
        return race([
            callcc(function($k) use($uri) {
                $this->get($uri, $k);
            }),
            timeout($timeout),
        ]);
    }

    public function awaitPost($uri, $post, $timeout = 1000)
    {
        return race([
            callcc(function($k) use($uri, $post) {
                $this->post($uri, $post, $k);
            }),
            timeout($timeout),
        ]);
    }

    public function awaitExecute($uri, $timeout = 1000)
    {
        return race([
            callcc(function($k) use($uri) {
                $this->execute($uri, $k);
            }),
            timeout($timeout),
        ]);
    }
}


function async_dns_lookup($host, $timeout = 100)
{
    return race([
        callcc(function($k) use($host) {
            swoole_async_dns_lookup($host, function($host, $ip) use($k) {
                $k($ip);
            });
        }),
        timeout($timeout),
    ]);
}

async(function() {
    try {
        $ip = (yield race([
            async_dns_lookup("www.baidu.com"),
            timeout(100),
        ]));
        $res = (yield (new HttpClient($ip, 80))->awaitGet("/"));
        var_dump($res->statusCode);
    } catch (\Exception $ex) {
        echo $ex;
        swoole_event_exit();
    }
});



// Any 表示多个异步回调, 任意返回则任务完成
// All 则需要回调全部返回


class All implements Async
{
    public $parent;
    public $tasks;
    public $continuation;

    public $n;
    public $results;
    public $done;

    public function __construct(array $tasks, AsyncTask $parent = null)
    {
        $this->tasks = $tasks;
        $this->parent = $parent;
        $this->n = count($tasks);
        assert($this->n > 0);
        $this->results = [];
    }

    /**
     * @param callable $continuation
     * @return void
     */
    public function begin(callable $continuation = null)
    {
        $this->continuation = $continuation;
        foreach ($this->tasks as $id => $task) {
            (new AsyncTask($task, $this->parent))->begin($this->continuation($id));
        };
    }

    private function continuation($id)
    {
        return function($r, $ex = null) use($id) {
            if ($this->done) {
                return;
            }

            if ($ex) {
                $this->done = true;
                $k = $this->continuation;
                $k(null, $ex);
                return;
            }

            $this->results[$id] = $r;
            if (--$this->n === 0) {
                $this->done = true;
                if ($this->continuation) {
                    $k = $this->continuation;
                    $k($this->results);
                }
            }
        };
    }
}


// 我们构建出与Promise.all 相似的接口
// 然后我们可以构建并发调用的接口
// 或者可以构造流水线批量任务, 队列等等

function all(array $tasks)
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


async(function() {
    $ex = null;
    try {
        $r = (yield all([
            async_dns_lookup("www.bing.com", 100),
            async_dns_lookup("www.so.com", 100),
            async_dns_lookup("www.baidu.com", 100),
        ]));
        var_dump($r);
        /*
array(3) {
  [0]=>
  string(14) "202.89.233.103"
  [1]=>
  string(14) "125.88.193.243"
  [2]=>
  string(15) "115.239.211.112"
}
         */
    } catch (\Exception $ex) {
        echo $ex;
    }
});



// 至此我们可以很方便的利用 callcc race all timeout 等基础设施处理现有的异步接口
// 对比 js thunk 与 promise, 个人觉得更简单一下
// 于是我么有了一个完整的协程调度器



// 让我们来构建一个koa