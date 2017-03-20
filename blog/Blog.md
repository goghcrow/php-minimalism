# PHP异步编程: 手把手教你实现co与Koa


近年来在面向高并发编程的道路上，nodejs与golang风生水起，人们渐渐把目光从多线程转移到Callback与CSP/Actor，
死守着同步阻塞模型的广大屌丝PHPer难免有人心动，各种EventLoop的扩展不温不火，最后swoole反客为主，
将完整的网络库通过扩展层暴露出来，于是我们有了一套相对完整的基于事件循环的Callback模型可用;

node之所以在js上开发结果，多半是因为js语言的函数式特性，适合异步回调代码编写，且浏览器的dom事件模型本身需要书写回调带来的行为习惯;
但回调固有的思维拆分、逻辑割裂、调试维护难的问题随着node社区的繁荣变得亟待解决，从老赵脑洞大开编译方案windjs到co与Promise，各种方案层出不穷，
最终[Promise](https://developer.mozilla.org/zh-CN/docs/Web/JavaScript/Reference/Global_Objects/Promise)被采纳为官方「异步编程标准规范」，
[async/await](https://developer.mozilla.org/zh-CN/docs/Web/JavaScript/Reference/Statements/async_function)被纳入语言标准；

因为模型相同, swoole中I/O接口同样以回调形式提供，PHPer"有幸"在高并发命题上的解决方案上遭遇与nodejs一样的问题；
我司去年开源[Zan](http://zanphp.io/)，内部构建一个与co类似的协程调度器(按wiki定义，确切来说是"半协程调度器")，
重新解决了回调代码书写的问题，但这并不妨碍我们造轮子的天性；





\Closure[RFC](https://wiki.php.net/rfc/closures?cm_mc_uid=26754990333314676210612&cm_mc_sid_50200000=1490031947)
与\Generator[RFC](https://wiki.php.net/rfc/generators)，一定程度从语言上改善了异步编程的体验;
(吐槽: 因为内部作用域实现原因，PHP缺失词法作用域，自然也缺失真正的词法闭包, \Closure对象"朴实"的采用了use这一略显诡异的语法来显式捕获upValue到\Closure对象的静态属性(closure->func.op_array.static_variables)，个人认为PHP仅仅算支持匿名函数，且PHP中匿名函数无法天然构成闭包)
(Generator资料请参考Nikita Popov文章的译文[在PHP中使用协程实现多任务调度 ](http://www.laruence.com/2015/05/28/3038.html))，



-----------------------------------------------------------------------------------------------


### co

谈及Koa首先要谈及co函数库，co与Promise诞生的初衷都是为了解决nodejs异步回调陷阱, 达到的目标是都是"同步书写异步代码";

co的核心是Generator自动执行器，或者说"异步迭代器"，通过yield显示操纵控制流实现半协程调度器;

(对co库不了解的同学可以参考[阮一峰 - co 函数库的含义和用法](http://www.ruanyifeng.com/blog/2015/05/co.html))

> 3.x
> Generator based flow-control goodness for nodejs and the browser, using thunks or promises, letting you write non-blocking code in a nice-ish way.
> 4.x
> Generator based control flow goodness for nodejs and the browser, using promises, letting you write non-blocking code in a nice-ish way.

co新版与旧版的区别在于对Promises的支持，虽然Promise是一套比较完善的方案，但是如何实现Promise本身超出本文范畴，

PHP也没有大量异步类库的历史包袱，需要thunks方案做转换，我们仅仅声明一个简单的接口，来抽象异步任务；

(声明interface动机是interface提供了一种可供检测的新类型，而不会在我们未来要实现的Generator执行器内部造成歧义;)

```php
<?php 
interface Async
{
    /**
     * 开启异步任务，完成是执行回调，任务结果或异常通过回调参数传递
     * @param callable $callback
     *      continuation :: (mixed $result = null, \Exception|null $ex = null)
     * @return void 
     */
    public function begin(callable $callback);
}
```

我们首先来实现koa的基础设施，使用50行左右代码渐进的实现一个更为精练的半协程调度器：

-----------------------------------------------------------------------------------------------


```php
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

function spawn()
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


spawn(function() {
    $ip = (yield async_dns_lookup("www.baidu.com"));
    $cli = new HttpClient($ip, 80);
    $cli->setHeaders(["foo" => "bar"]);
    $cli = (yield $cli->awaitGet("/"));
    echo $cli->body, "\n";
});


// 我们可以用相似的方案来封装swoole的异步接口，比如TcpClient,MysqlClient,RedisClient
// 推荐继承swoole原生类, 然后单独添加协程接口



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
//spawn(function() {
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

//spawn(function() {
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

spawn(function() {
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


spawn(function() {
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
// 对比 js thunk 与 promise, 个人觉得更简单易用
// 于是我么有了一个完整的协程调度器

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

// 我们已经构建了基于yield语义的半协程，接下来构建最后一个基础设施，Channel
// 没错，就是golang的channel

// https://tour.golang.org/concurrency/2
// 因为我们的协程是1:n的模型，对于chan发送与接收的阻塞的处理，最终转换为对
// 使用chan的协程的控制流的控制
// 我们首先实现无缓存的Channel


// > By default, sends and receives block until the other side is ready.
// This allows goroutines to synchronize without explicit locks or condition variables.

class Channel
{
    // 因为同一个channel可能有多个接收者，使用队列实现，保证调度均衡
    // 队列内保存的是被阻塞的接收者协程的控制流，即call/cc的参数，我们模拟的continuation
    public $recvQ;
    // 发送者队列逻辑相同
    public $sendQ;

    public function __construct()
    {
        $this->recvQ = new \SplQueue();
        $this->sendQ = new \SplQueue();
    }

    public function send($val)
    {
        return callcc(function($cc) use($val) {
            if ($this->recvQ->isEmpty()) {
                // 当chan没有接收者，发送者协程挂起(将$cc入列，不调用$cc回送数据)
                $this->sendQ->enqueue([$cc, $val]);
            } else {
                // 当chan对端有接收者，将挂起接收者协程出列，
                // 调用接收者$recvCc发送数据，运行接收者协程后继代码
                // 执行完毕或者遇到Async挂起，$recvCc()调用返回，
                // 调用$cc()，控制流回到发送者协程
                $recvCc = $this->recvQ->dequeue();
                $recvCc($val, null);
                $cc(null, null);
            }
        });
    }

    public function recv()
    {
        return callcc(function($cc) {
            if ($this->sendQ->isEmpty()) {
                // 当chan没有发送者，接收者协程挂起（将$cc入列）
                $this->recvQ->enqueue($cc);
            } else {
                // 当chan对端有发送者，将挂起发送者协程与待发送数据出列
                // 调用发送者$sendCc发送数据，运行发送者协程后继代码
                // 执行完毕或者遇到Async挂起，$sendCc()调用返回，
                // 调用$cc()，控制流回到接收者协程
                list($sendCc, $val) = $this->sendQ->dequeue();
                $sendCc(null, null);
                $cc($val, null);
            }
        });
    }
}



// 接下来我们来实现带缓存的Channel
// > Sends to a buffered channel block only when the buffer is full. Receives block when the buffer is empty.

class BufferChannel
{
    // 缓存容量
    public $cap;
    // 缓存
    public $queue;
    // 同无缓存Channel
    public $recvCc;
    // 同无缓存Channel
    public $sendCc;

    public function __construct($cap)
    {
        assert($cap > 0);
        $this->cap = $cap;
        $this->queue = new \SplQueue();
        $this->sendCc = new \SplQueue();
        $this->recvCc = new \SplQueue();
    }

    public function recv()
    {
        return callcc(function($cc) {
            if ($this->queue->isEmpty()) {
                // 当无数据可接收时, $cc入列，让出控制流，挂起接收者协程
                $this->recvCc->enqueue($cc);
            } else {
                // 当有数据可接收时, 先接收数据，然后恢复控制流
                $val = $this->queue->dequeue();
                $this->cap++;
                $cc($val, null);
            }

            // 递归的唤醒其他被阻塞的发送者与接收者收发数据，注意顺序
            $this->recvPingPong();
        });
    }

    public function send($val)
    {
        return callcc(function($cc) use($val) {
            if ($this->cap > 0) {
                // 当缓存未满，发送数据直接加入缓存，然后恢复控制流
                $this->queue->enqueue($val);
                $this->cap--;
                $cc(null, null);
            } else {
                // 当缓存满，发送者控制流与发送数据入列，让出控制流，挂起发送者协程
                $this->sendCc->enqueue([$cc, $val]);
            }

            // 递归的唤醒其他被阻塞的接收者与发送者收发数据，注意顺序
            $this->sendPingPong();

            // 如果全部代码都为同步，防止多个发送者时，当数据全部来自某个发送者
            // 应该把sendPingPong 修改为异步执行 defer([$this, "sendPingPong"]);
            // 但是swoole本身的defer实现有bug，除非把defer 实现为swoole_timer_after(1, ...)
            // recvPingPong 同理
        });
    }

    public function recvPingPong()
    {
        // 当有阻塞的发送者，唤醒其发送数据
        if (!$this->sendCc->isEmpty() && $this->cap > 0) {
            list($sendCc, $val) = $this->sendCc->dequeue();
            $this->queue->enqueue($val);
            $this->cap--;
            $sendCc(null, null);

            // 当有阻塞的接收者，唤醒其接收数据
            if (!$this->recvCc->isEmpty() && !$this->queue->isEmpty()) {
                $recvCc = $this->recvCc->dequeue();
                $val = $this->queue->dequeue();
                $this->cap++;
                $recvCc($val);

                $this->recvPingPong();
            }
        }
    }

    public function sendPingPong()
    {
        // 当有阻塞的接收者，唤醒其接收数据
        if (!$this->recvCc->isEmpty() && !$this->queue->isEmpty()) {
            $recvCc = $this->recvCc->dequeue();
            $val = $this->queue->dequeue();
            $this->cap++;
            $recvCc($val);

            // 当有阻塞的发送者，唤醒其发送数据
            if (!$this->sendCc->isEmpty() && $this->cap > 0) {
                list($sendCc, $val) = $this->sendCc->dequeue();
                $this->queue->enqueue($val);
                $this->cap--;
                $sendCc(null, null);

                $this->sendPingPong();
            }
        }
    }
}





// 我们封装下接口
function go()
{
    spawn(...func_get_args());
}

function chan($n = 0)
{
    if ($n === 0) {
        return new Channel();
    } else {
        return new BufferChannel($n);
    }
}




// 我们用协程构建一个简单的生产者消费者模型

$ch = chan();

go(function() use($ch) {
    while (true) {
        yield $ch->send("producer 1");
        yield async_sleep(1000);
    }
});

go(function() use($ch) {
    while (true) {
        yield $ch->send("producer 2");
        yield async_sleep(1000);
    }
});

go(function() use($ch) {
    while (true) {
        $recv = (yield $ch->recv());
        echo "consumer1: $recv\n";
    }
});

go(function() use($ch) {
    while (true) {
        $recv = (yield $ch->recv());
        echo "consumer2: $recv\n";
    }
});

// output:
/*
consumer1 recv from producer 1
consumer1 recv from producer 2
consumer1 recv from producer 1
consumer2 recv from producer 2
consumer1 recv from producer 2
consumer2 recv from producer 1
consumer1 recv from producer 1
consumer2 recv from producer 2
consumer1 recv from producer 2
consumer2 recv from producer 1
consumer1 recv from producer 1
consumer2 recv from producer 2
......
*/



// chan 本身是可传递的

$ch = chan();

go(function() use ($ch) {
    $anotherCh = chan();
    yield $ch->send($anotherCh);
    echo "send another channel\n";
    yield $anotherCh->send("HELLO");
    echo "send hello through another channel\n";
});

go(function() use($ch) {
    /** @var Channel $anotherCh */
    $anotherCh = (yield $ch->recv());
    echo "recv another channel\n";
    $val = (yield $anotherCh->recv());
    echo $val, "\n";
});

// output:
/*
send another channel
recv another channel
send hello through another channel
HELLO
*/




// 我们通过控制channel缓存大小 观察输出结果

$ch = chan($n);

go(function() use($ch) {
    $recv = (yield $ch->recv());
    echo "recv $recv\n";
    $recv = (yield $ch->recv());
    echo "recv $recv\n";
    $recv = (yield $ch->recv());
    echo "recv $recv\n";
    $recv = (yield $ch->recv());
    echo "recv $recv\n";
});

go(function() use($ch) {
    yield $ch->send(1);
    echo "send 1\n";
    yield $ch->send(2);
    echo "send 2\n";
    yield $ch->send(3);
    echo "send 3\n";
    yield $ch->send(4);
    echo "send 4\n";
});

// $n = 1;
// output:
/*
send 1
recv 1
send 2
recv 2
send 3
recv 3
send 4
recv 4
*/

// $n = 2;
// output:
/*
send 1
send 2
recv 1
recv 2
send 3
send 4
recv 3
recv 4
*/

// $n = 3;
// output:
/*
send 1
send 2
send 3
recv 1
recv 2
recv 3
send 4
recv 4
*/



// TODO 现在的发送与接受没有超时机制，golang需要select多个chan实现超时处理
// 我们可以在send于recv接受直接添加超时参数，进行功能扩展

$ch = chan(2);

go(function() use($ch) {
    while (true) {
        list($host, $status) = (yield $ch->recv());
        echo "$host: $status\n";
    }
});

go(function() use($ch) {
    while (true) {
        $host = "www.baidu.com";
        $resp = (yield async_curl_get($host));
        yield $ch->send([$host, $resp->statusCode]);
    }
});

go(function() use($ch) {
    while (true) {
        $host = "www.bing.com";
        $resp = (yield async_curl_get($host));
        yield $ch->send([$host, $resp->statusCode]);
    }
});

// output:
```


    $pingCh = chan();
    $pongCh = chan();
    
    go(function() use($pingCh, $pongCh) {
        while (true) {
            echo (yield $pingCh->recv());
            yield $pongCh->send("PONG\n");

            yield async_sleep(1);
        }
    });
    
    go(function() use($pingCh, $pongCh) {
        while (true) {
            echo (yield $pongCh->recv());
            yield $pingCh->send("PING\n");

            yield async_sleep(1);
        }
    });
    
    go(function() use($pingCh) {
        echo "start up\n";
        yield $pingCh->send("PING");
    });

// output:
/*
start up
PING
PONG
PING
PONG
PING
PONG
PING
...
*/

-----------------------------------------------------------------------------------------------








### Koa

Koa自述是下一代web框架：

> 由 Express 原班人马打造的 koa，致力于成为一个更小、更健壮、更富有表现力的 Web 框架。
> 使用 koa 编写 web 应用，通过组合不同的 generator，
> 可以免除重复繁琐的回调函数嵌套，并极大地提升常用错误处理效率。
> Koa 不在内核方法中绑定任何中间件，它仅仅提供了一个轻量优雅的函数库，使得编写 Web 应用变得得心应手。


Koa是那种与martini一样，设计清爽的框架，
我们可以用少量的代码基于PHP5.6与yz-swoole(有赞内部自研稳定版本的Swoole，暂且等待，即将发布)重新发明；
martini与Koa都属于中间件web框架，采用洋葱模型middleware stack，自身没有提供任何业务相关库，但提供了强大的灵活性,

对于web应用来讲，所有业务逻辑归根结底都在处理请求与相应对象,
web中间件实质就是在请求与响应中间开放出来的可编排的扩展点，
比如在修改请求做URLRewrite，比如身份验证，安全拦截；

真正的业务逻辑都可以通过middleware实现，或者说按特定顺序对中间件灵活组合编排;
Koa对中间编写的贡献是，合并req + res对象，封装组合方式，让编写更直观方便；
因为koa2.x决定全面使用async/await, 我们这里使用PHP实现koa1.x，
仍旧将Generator作为middleware实现形式.

-----------------------------------------------------------------------------------------------


```php
<?php


// 作为一个表达能力很强中间件框架, koa本身只有极少的通用组件, 其中最有价值的是
// > Koa's middleware stack flows in a stack-like manner,
// > allowing you to perform actions downstream then filter and manipulate the response upstream.
// KOA 地址
// koa 只有四个组件, Application, Context, Request, Response

/*
+Each middleware receives a Koa `Context` object that encapsulates an incoming
+http message and the corresponding response to that message.  `ctx` is often used
+as the parameter name for the context object.
 */

// koa readme !!!
// koa是中间件框架,中间件可以用callable与Generator表示
// 参数约定 ctx, next
/*



+The `Context` object also provides shortcuts for methods on its `request` and `response`.  In the prior
+examples,  `ctx.type` can be used instead of `ctx.request.type` and `ctx.accepts` can be used
+instead of `ctx.request.accepts`.


## Koa Application
+
+The object created when executing `new Koa()` is known as the Koa application object.
+
+The application object is Koa's interface with node's http server and handles the registration
+of middleware, dispatching to the middleware from http, default error handling, as well as
+configuration of the context, request and response objects.

*/

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

// 但凡web框架都会有拦截器或者过滤器的组件, aop
// Ruby Rack
// Golang matini
// Python django
// Node Express, Koa 都有一个类似的中间件系统
// 对于web应用来讲，中间件在请求与响应中间开放出来可编排的扩展点，改写req res


// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

// 演示koa的中间件之前，我们先来一场穿越地心之旅

// https://zh.wikipedia.org/wiki/%E5%9C%B0%E7%90%83%E6%A7%8B%E9%80%A0
// 物理学上，地球可划分为岩石圈、软流层、地幔、外核和内核5层。
// 化学上，地球被划分为地壳、上地幔、下地幔、外核和内核。地质学上对地球各层的划分


function crust($next)
{
    return function() use($next) {
        echo "到达<地壳>\n";
        $next();
        echo "离开<地壳>\n";
    };
}

function upperMantle($next)
{
    return function() use($next) {
        echo "到达<上地幔>\n";
        $next();
        echo "离开<上地幔>\n";
    };
}

function mantle($next)
{
    return function() use($next) {
        echo "到达<下地幔>\n";
        $next();
        echo "离开<下地幔>\n";
    };
}

function outerCore($next)
{
    return function() use($next) {
        echo "到达<外核>\n";
        $next();
        echo "离开<外核>\n";
    };
}

function innerCore($next)
{
    return function() {
        echo "到达<内核>\n";
    };
}


function makeTravel(...$layers)
{
    $next = null;
    $i = count($layers);
    while ($i--) {
        $next = $layers[$i]($next);
    }
    return $next;
}

//$travel = makeTravel("crust", "upperMantle", "mantle", "outerCore", "innerCore");
//$travel(); // output:


/*
到达<地壳>
到达<上地幔>
到达<下地幔>
到达<外核>
到达<内核>
离开<外核>
离开<下地幔>
离开<上地幔>
离开<地壳>
*/


// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

function outerCore1($next)
{
    return function() use($next) {
        echo "到达<外核>\n";
        // 我们放弃内核, 仅仅绕外壳一周, 从另一侧返回地表
        // $next();
        echo "离开<外核>\n";
    };
}

//$travel = makeTravel("crust", "upperMantle", "mantle", "outerCore1", "innerCore");
//$travel(); // output:
/*
到达<地壳>
到达<上地幔>
到达<下地幔>
到达<外核>
离开<外核>
离开<下地幔>
离开<上地幔>
离开<地壳>
*/

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=


function innerCore1($next)
{
    return function() {
        // 我们到达内核之前遭遇了岩浆，计划终止，等待救援
        throw new \Exception("岩浆");
        echo "到达<内核>\n";
    };
}

//$travel = makeTravel("crust", "upperMantle", "mantle", "outerCore", "innerCore1");
//$travel(); // output:
/*
到达<地壳>
到达<上地幔>
到达<下地幔>
到达<外核>
Fatal error: Uncaught exception 'Exception' with message '岩浆'
*/


// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

function mantle1($next)
{
    return function() use($next) {
        echo "到达<下地幔>\n";
        // 我们再下地幔的救援团队及时赶到 (try catch)
        try {
            $next();
        } catch (\Exception $ex) {
            echo "遇到", $ex->getMessage(), "\n";
        }
        // 我们仍旧没有去往内核, 绕道对面下地幔, 返回地表
        echo "离开<下地幔>\n";
    };
}

//$travel = makeTravel("crust", "upperMantle", "mantle1", "outerCore", "innerCore1");
//$travel(); // output:
/*
到达<地壳>
到达<上地幔>
到达<下地幔>
到达<外核>
遇到岩浆
离开<下地幔>
离开<上地幔>
离开<地壳>
*/

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

function upperMantle1($next)
{
    return function() use($next) {
        // 我们放弃对去程上地幔的暂留
        // echo "到达<上地幔>\n";
        $next();
        // 只在返程时暂留
        echo "离开<上地幔>\n";
    };
}

function outerCore2($next)
{
    return function() use($next) {
        // 我们决定只在去程考察外壳
        echo "到达<外核>\n";
        $next();
        // 因为温度过高，去程匆匆离开外壳
        // echo "离开<外核>\n";
    };
}

$travel = makeTravel("crust", "upperMantle1", "mantle1", "outerCore2", "innerCore1");
$travel(); // output:
/*
到达<地壳>
到达<上地幔>
到达<下地幔>
到达<外核>
遇到岩浆
离开<下地幔>
离开<上地幔>
离开<地壳>
*/


// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

/*
```
            --------------------------------------
            |            middleware1              |
            |    ----------------------------     |
            |    |       middleware2         |    |
            |    |    -------------------    |    |
            |    |    |  middleware3    |    |    |
            |    |    |                 |    |    |
          next next next  ———————————   |    |    |
请求 ——————————————————> |  handler  |   — 收尾工作->|
响应 <—————————————————  |     G     |   |    |    |
            | A  | C  | E ——————————— F |  D |  B |
            |    |    |                 |    |    |
            |    |    -------------------    |    |
            |    ----------------------------     |
            --------------------------------------


顺序 A -> C -> E -> G -> F -> D -> B
    \---------------/   \----------/
            ↓                ↓
        请求响应完毕        收尾工作
```
*/


// 输出结构演示了这种洋葱圈结构的实现方式与执行流程

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=


// 我们把函数从内向外组合, 把内层函数的执行控制权包裹成next参数传递给外层函数
// 让外层函数自行控制内层函数执行时机, 我们再一次把控制流暴露出来
// 第一次是引入continuation, 把return决定的控制流暴露到参数中
// 于是 我们可以在外层函数 执行next时, 前后加入自己的逻辑, 得到 AOP 的before与after语义
// 但是不仅仅如此, 我们甚至可以不执行内层函数, 然后我们穿越地心的过程沿着某个半圆绕过了地核 （画图）
// 抑或，我们也可以放弃after，函数则成为 前置过滤器(Filter)，如果我们放弃before，函数则成为 后置过滤器(Terminator)
// 关于错误处理, 我们可以在某层的函数 try-catch next调用, 从而阻止内层函数的异常向上传递
// 想想我们在地底深处包裹了一层可以抵御岩浆外太空物质， 岩浆被安全的舒服到了地心
// 我们得到一个极简且巧妙的洋葱圈结构

// signature middleware :: (Context $ctx, $next) -> void
// 我们把多个满足中间件签名的若干个函数(callable)组合成一个函数

// 稍加改造, 我们把compose修改为生成器函数, 用来支持我们之前做的协程执行器async/go
function compose(...$middleware)
{
    return function(Context $ctx = null, $next = null) use($middleware) {
        $ctx = $ctx ?: new Context();
        $next = $next ?: function() { yield; };

        $i = count($middleware);
        while ($i--) {
            $curr = $middleware[$i];
            $next = $curr($ctx, $next);
        }
        yield $next;
    };
}

/*
Method Combination
 Build a method from components in different classes
 Primary methods: the “normal” methods; choose the
most specific one
 Before/After methods: guaranteed to run;
No possibility of forgetting to call super
Can be used to implement Active Value pattern
 Around methods: wrap around everything;
Used to add tracing information, etc.
 Is added complexity worth it?
Common Lisp: Yes; Most languages: No


First-Class Dynamic Functions
 Functions are objects too
 Functions are composed of methods
 There are operations on functions (compose, conjoin)
 Code is organized around functions as well as classes
 Function closures capture local state variables
(Objects are state data with attached behavior;
Closures are behaviors with attached state data
and without the overhead of classes.)
 */

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=



// 参考 https://github.com/guo-yu/koa-guide

// 1. App 模块是对swoole_http_server 的简单封装
// 在onRequest回调中执行composer之后的中间件
// koa 最大的优势是中间件系统，比传统的做法多了一层逆序回调，像我们之前展示的case，
// koa中间件的原理与 Ruby Rack 或者 Connect/Express 或者 Django Middleware可能没有太大区别
// 但是提供了一种最为简单明了的中间件编写方法



class Application
{
    /** @var \swoole_http_server */
    public $httpServer;
    /** @var Context Prototype Context */
    public $context;
    public $middleware = [];
    public $fn;

    public function __construct()
    {
        // 我们构造一个Context原型
        $this->context = new Context();
        $this->context->app = $this;
    }

    // 我们用υse方法添加符合接口的中间件
    // middleware :: (Context $ctx, $next) -> void
    public function υse(callable $fn)
    {
        $this->middleware[] = $fn;
        return $this;
    }

    // compose中间件监听端口提供服务
    public function listen($port = 8000, array $config = [])
    {
        $this->fn = compose(...$this->middleware);
        $config = ['port' => $port] + $config + $this->defaultConfig();
        $this->httpServer = new \swoole_http_server($config['host'], $config['port'], SWOOLE_PROCESS, SWOOLE_SOCK_TCP);
        $this->httpServer->set($config);
        // ......省略代码
        // 绑定 swoole HttpServer 事件, start shutdown connect close workerStart workerStop workerError request
        $this->httpServer->start();
    }

    public function onRequest(\swoole_http_request $req, \swoole_http_response $res)
    {
        $ctx = $this->createContext($req, $res);
        $reqHandler = $this->makeRequestHandler($ctx);
        $resHandler = $this->makeResponseHandler($ctx);
        spawn($reqHandler, $resHandler);
    }

    protected function makeRequestHandler(Context $ctx)
    {
        return function() use($ctx) {
            yield setCtx("ctx", $ctx);
            $ctx->res->status(404);
            $fn = $this->fn;
            yield $fn($ctx);
        };
    }

    protected function makeResponseHandler(Context $ctx)
    {
        return function($r = null, \Exception $ex = null) use($ctx) {
            if ($ex) {
                $this->handleError($ctx, $ex);
            } else {
                $this->respond($ctx);
            }
        };
    }

    protected function handleError(Context $ctx, \Exception $ex = null)
    {
        if ($ex === null) {
            return;
        }

        if ($ex && $ex->getCode() !== 404) {
            sys_error($ctx);
            sys_error($ex);
        }

        // 非 Http异常, 统一500 status, 对外显示异常code
        // Http 异常, 自定义status, 自定义是否暴露Msg
        $msg = $ex->getCode();
        if ($ex instanceof HttpException) {
            $status = $ex->status ?: 500;
            $ctx->res->status($status);
            if ($ex->expose) {
                $msg = $ex->getMessage();
            }
        } else {
            $ctx->res->status(500);
        }

        // force text/plain
        $ctx->res->header("Content-Type", "text"); // TODO accepts
        $ctx->res->write($msg);
        $ctx->res->end();
    }

    protected function respond(Context $ctx)
    {
        if ($ctx->respond === false) return; // allow bypassing koa

        $body = $ctx->body;
        $code = $ctx->status;

        if ($code !== null) {
            $ctx->res->status($code);
        }
        // status.empty() $ctx->body = null; res->end()

        if ($body !== null) {
            $ctx->res->write($body);
        }

        $ctx->res->end();
    }

    protected function createContext(\swoole_http_request $req, \swoole_http_response $res)
    {
        // 可以在Context挂其他组件 $app->foo = bar; $app->listen();
        $context = clone $this->context;

        $request = $context->request = new Request($this, $context, $req, $res);
        $response = $context->response = new Response($this, $context, $req, $res);

        $context->app = $this;
        $context->req = $req;
        $context->res = $res;

        $request->response = $response;
        $response->request = $request;

        $request->originalUrl = $req->server["request_uri"];
        $request->ip = $req->server["remote_addr"];

        return $context;
    }
}


// Context 组件代理了Request与Response中的方法和属性，简化了使用方式与中间件接口
// 这里用php的魔术方法来简化处理

class Context
{
    public $app;
    /** @var Request */
    public $request;
    /** @var Response */
    public $response;
    /** @var \swoole_http_request */
    public $req;
    /** @var \swoole_http_response */
    public $res;
    public $state = [];
    public $respond = true;
    /** @var string */
    public $body;
    /** @var int */
    public $status;

    public function __call($name, $arguments)
    {
        $fn = [$this->response, $name];
        return $fn(...$arguments);
    }

    public function __get($name)
    {
        return $this->request->$name;
    }

    public function __set($name, $value)
    {
        $this->response->$name = $value;
    }

    // thr[ο]w ο 为希腊字母 Ομικρον
    public function thrοw($status, $message)
    {
        if ($message instanceof \Exception) {
            $ex = $message;
            throw new HttpException($status, $ex->getMessage(), $ex->getCode(), $ex->getPrevious());
        } else {
            throw new HttpException($status, $message);
        }
    }
}


// Request 组件是对swoole_http_request 的简易封装

class Request
{
    /** @var Application */
    public $app;
    /** @var \swoole_http_request */
    public $req;
    /** @var \swoole_http_response */
    public $res;
    /** @var Context */
    public $ctx;
    /** @var Response */
    public $response;
    /** @var string */
    public $originalUrl;
    /** @var string */
    public $ip;

    public function __construct(Application $app, Context $ctx,
                                \swoole_http_request $req, \swoole_http_response $res)
    {
        $this->app = $app;
        $this->ctx = $ctx;
        $this->req = $req;
        $this->res = $res;
    }

    public function __get($name)
    {
        switch ($name) {
            case "rawcontent":
                return $this->req->rawContent();
            case "post":
                return isset($this->req->post) ? $this->req->post : [];
            case "get":
                return isset($this->req->get) ? $this->req->get : [];
            case "cookie":
            case "cookies":
                return isset($this->req->cookie) ? $this->req->cookie : [];
            case "request":
                return isset($this->req->request) ? $this->req->request : [];
            case "header":
            case "headers":
                return isset($this->req->header) ? $this->req->header : [];
            case "files":
                return isset($this->req->files) ? $this->req->files : [];
            case "method":
                return $this->req->server["request_method"];
            case "url":
            case "origin":
                return $this->req->server["request_uri"];
            case "path":
                return isset($this->req->server["path_info"]) ? $this->req->server["path_info"] : "";
            case "query":
            case "querystring":
                return isset($this->req->server["query_string"]) ? $this->req->server["query_string"] : "";
            case "host":
            case "hostname":
                return isset($this->req->header["host"]) ? $this->req->header["host"] : "";
            case "protocol":
                return $this->req->server["server_protocol"];
            default:
                return $this->req->$name;
        }
    }
}


// Response 组件是对swoole_http_response 的简易封装

class Response
{
    /* @var Application */
    public $app;
    /** @var \swoole_http_request */
    public $req;
    /** @var \swoole_http_response */
    public $res;
    /** @var Context */
    public $ctx;
    /** @var Request */
    public $request;
    public $isEnd = false;

    public function __construct(Application $app, Context $ctx,
                                \swoole_http_request $req, \swoole_http_response $res)
    {
        $this->app = $app;
        $this->ctx = $ctx;
        $this->req = $req;
        $this->res = $res;
    }

    public function __call($name, $arguments)
    {
        /** @var $fn callable */
        $fn = [$this->res, $name];
        return $fn(...$arguments);
    }

    public function __get($name)
    {
        return $this->res->$name;
    }

    public function __set($name, $value)
    {
        switch ($name) {
            case "type":
                return $this->res->header("Content-Type", $value);
            case "lastModified":
                return $this->res->header("Last-Modified", $value);
            case "etag":
                return $this->res->header("ETag", $value);
            case "length":
                return $this->res->header("Content-Length", $value);
            default:
                return $this->res->header($name, $value);
        }
    }

    public function end($html = "")
    {
        if ($this->isEnd) {
            return false;
        }
        $this->isEnd = true;
        return $this->res->end($html);
    }

    public function redirect($url, $status = 302)
    {
        $this->res->header("Location", $url);
        $this->res->header("Content-Type", "text/plain; charset=utf-8");
        $this->ctx->status = $status;
        $this->ctx->body = "Redirecting to $url.";
    }

    public function render($file)
    {
        $this->ctx->body = (yield Template::render($file, $this->ctx->state));
    }
}

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

// example:

$app = new Application();
$app->υse(function(Context $ctx) {
    $ctx->status = 200;
    $ctx->body = "<h1>Hello World</h1>";
});
$app->listen(3000);

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=


$app->υse(function(Context $ctx, $next) {
    $start = microtime(true);
    yield $next;
    $ms = number_format(microtime(true) - $start, 7);
    // response header 写入 X-Response-Time: xxxms
    $ctx->{"X-Response-Time"} = "{$ms}ms";
    sys_echo("$ctx->method $ctx->url - $ms");
});


// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

// 全局异常处理中间件
// 需要在 业务逻辑中间件 前use
// catch 在 ExceptionHandler之后use的中间件中未捕获异常
// 这里插入一点，中间件的use顺序非常重要，比如这里的异常，必须优先use，才可以捕获下层中间件
// 抛出的异常，又比如session中间件，需要优先于业务处理中间件
// 而像处理404状态码的中间件，也需要高优先级，但是逻辑只会在upstream逆序调用，即next之后

class ExceptionHandler implements Middleware
{
    public function __invoke(Context $ctx, $next)
    {
        try {
            yield $next;
        } catch (\Exception $ex) {
            $status = 500;
            $code = $ex->getCode() ?: 0;
            $msg = "Internal Error";

            // HttpException的异常通常是通过Context的throw方法抛出
            // 状态码与Msg直接提取可用
            if ($ex instanceof HttpException) {
                $status = $ex->status;
                if ($ex->expose) {
                    $msg = $ex->getMessage();
                }
            }
            // 这里可这对其他异常区分处理
            // else if ($ex instanceof otherException) { }

            $err = [ "code" => $code,  "msg" => $msg ];
            if ($ctx->accept("json")) {
                $ctx->status = 200;
                $ctx->body = $err;
            } else {
                $ctx->status = $status;
                if ($status === 404) {
                    $ctx->body = (yield Template::render(__DIR__ . "/404.html"));
                } else if ($status === 500) {
                    $ctx->body = (yield Template::render(__DIR__ . "/500.html", $err));
                } else {
                    $ctx->body = (yield Template::render(__DIR__ . "/error.html", $err));
                }
            }
        }
    }
}


$app->υse(new ExceptionHandler());

// 可以将FastRoute与Exception中间件结合
// 很容可以定制一个针对路由注册的灵活的异常处理器




// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

// 为了更好阻止代码，我们为Middleware声明一个接口

interface Middleware
{
    public function __invoke(Context $ctx, $next);
}

// 实现该接口的对象本身满足 callable 类型，我们的中间件接受任何callable
// 可以是 function, Closure, array 等

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

// 路由是httpServer必不可少的组件，我们考虑如何实现一个路由中间件

// 路由解析部分我们nikic的fast-route
// "nikic/fast-route": "dev-master"

//use FastRoute\Dispatcher;
//use FastRoute\RouteCollector;
//use Minimalism\A\Server\Http\Context;

// 这里继承FastRoute的RouteCollector
class Router extends RouteCollector
{
    /**
     * @var Dispatcher
     */
    public $dispatcher;

    public function __construct()
    {
        $routeParser = new \FastRoute\RouteParser\Std();
        $dataGenerator = new \FastRoute\DataGenerator\GroupCountBased();
        parent::__construct($routeParser, $dataGenerator);
    }

    public function routes()
    {
        $this->dispatcher = new \FastRoute\Dispatcher\GroupCountBased($this->getData());
        return [$this, "dispatch"];
    }

    // 路由中间件的主要逻辑
    public function dispatch(Context $ctx, $next)
    {
        if ($this->dispatcher === null) {
            $this->routes();
        }

        $uri = $ctx->url;
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);

        // 从Context提取method与url进行分发
        $routeInfo = $this->dispatcher->dispatch(strtoupper($ctx->method), $uri);
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                // 状态码写入Context
                $ctx->status = 404;
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                $ctx->status = 405;
                break;
            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];
                // 从路由表提取处理器
                $handler($ctx, $next, $vars);
                break;
        }
    }
}


// https://github.com/nikic/FastRoute
$router = new Router();
$router->get('/user/{id:\d+}', function(Context $ctx, $next, $vars) {
    $ctx->body = "user={$vars['id']}";
});
// $route->post('/post-route', 'post_handler');
$router->addRoute(['GET', 'POST'], '/test', function(Context $ctx, $next, $vars) {
    $ctx->body = "";
});
// 分组路由
$router->addGroup('/admin', function (RouteCollector $router) {
    // handler :: (Context $ctx, $next, array $vars) -> void
    $router->addRoute('GET', '/do-something', 'handler');
    $router->addRoute('GET', '/do-another-thing', 'handler');
    $router->addRoute('GET', '/do-something-else', 'handler');
});

$app->υse($router->routes());


// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=


// 全局请求超时处理器
// 同时也可以结合FastRoute构造出一个针对特定路由匹配不同请求超时时间的中间件

class Timeout implements Middleware
{
    public $timeout;
    public $exception;

    private $timerId;

    public function __construct($timeout, \Exception $ex = null)
    {
        $this->timeout = $timeout;
        if ($ex === null) {
            $this->exception = new HttpException(408, "Request timeout");
        } else {
            $this->exception = $ex;
        }
    }

    public function __invoke(Context $ctx, $next)
    {
        yield race([
            callcc(function($k) {
                $this->timerId = swoole_timer_after($this->timeout, function() use($k) {
                    $k(null, $this->exception);
                });
            }),
            function() use ($next){
                yield $next;
                if (swoole_timer_exists($this->timerId)) {
                    swoole_timer_clear($this->timerId);
                }
            },
        ]);
    }
}

$app->υse(new Timeout(2000));

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
```