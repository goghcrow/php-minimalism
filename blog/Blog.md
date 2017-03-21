# PHP异步编程: 手把手教你实现co与Koa


近年来在面向高并发编程的道路上,nodejs与golang风生水起,人们渐渐把目光从多线程转移到Callback与CSP/Actor,
死守着同步阻塞模型的广大屌丝PHPer难免有人心动,各种EventLoop的扩展不温不火,最后swoole反客为主,
将完整的网络库通过扩展层暴露出来,于是我们有了一套相对完整的基于事件循环的Callback模型可用;

node之所以在js上开发结果,多半是因为js语言的函数式特性,适合异步回调代码编写,且浏览器的dom事件模型本身需要书写回调带来的行为习惯;
但回调固有的思维拆分、逻辑割裂、调试维护难的问题随着node社区的繁荣变得亟待解决,从老赵脑洞大开编译方案windjs到co与Promise,各种方案层出不穷,
最终[Promise](https://developer.mozilla.org/zh-CN/docs/Web/JavaScript/Reference/Global_Objects/Promise)被采纳为官方「异步编程标准规范」,
[async/await](https://developer.mozilla.org/zh-CN/docs/Web/JavaScript/Reference/Statements/async_function)被纳入语言标准；

因为模型相同, swoole中I/O接口同样以回调形式提供,PHPer"有幸"在高并发命题上的解决方案上遭遇与nodejs一样的问题；
我司去年开源[Zan](http://zanphp.io/),内部构建一个与co类似的协程调度器(按wiki定义,确切来说是"半协程调度器"),
重新解决了回调代码书写的问题,但这并不妨碍我们造轮子的天性；





\Closure[RFC](https://wiki.php.net/rfc/closures?cm_mc_uid=26754990333314676210612&cm_mc_sid_50200000=1490031947)
与\Generator[RFC](https://wiki.php.net/rfc/generators),一定程度从语言上改善了异步编程的体验;
(吐槽: 因为内部作用域实现原因,PHP缺失词法作用域,自然也缺失真正的词法闭包, \Closure对象"朴实"的采用了use这一略显诡异的语法来显式捕获upValue到\Closure对象的静态属性(closure->func.op_array.static_variables),个人认为PHP仅仅算支持匿名函数,且PHP中匿名函数无法天然构成闭包)
(Generator资料请参考Nikita Popov文章的译文[在PHP中使用协程实现多任务调度 ](http://www.laruence.com/2015/05/28/3038.html)),



-----------------------------------------------------------------------------------------------


### co

谈及Koa首先要谈及co函数库,co与Promise诞生的初衷都是为了解决nodejs异步回调陷阱, 达到的目标是都是"同步书写异步代码";

co的核心是Generator自动执行器,或者说"异步迭代器",通过yield显示操纵控制流实现半协程调度器;

(对co库不了解的同学可以参考[阮一峰 - co 函数库的含义和用法](http://www.ruanyifeng.com/blog/2015/05/co.html))

> 3.x
> Generator based flow-control goodness for nodejs and the browser, using thunks or promises, letting you write non-blocking code in a nice-ish way.
> 4.x
> Generator based control flow goodness for nodejs and the browser, using promises, letting you write non-blocking code in a nice-ish way.

co新版与旧版的区别在于对Promises的支持,虽然Promise是一套比较完善的方案,但是如何实现Promise本身超出本文范畴,

PHP也没有大量异步类库的历史包袱,需要thunks方案做转换,我们仅仅声明一个简单的接口,来抽象异步任务；

(声明interface动机是interface提供了一种可供检测的新类型,而不会在我们未来要实现的Generator执行器内部造成歧义;)

```php
<?php 
interface Async
{
    /**
     * 开启异步任务,完成是执行回调,任务结果或异常通过回调参数传递
     * @param callable $callback
     *      continuation :: (mixed $result = null, \Exception|null $ex = null)
     * @return void 
     */
    public function begin(callable $callback);
}
```

我们首先来实现koa的基础设施,使用50多行代码渐进的实现一个更为精练的半协程调度器：

-----------------------------------------------------------------------------------------------

**注意: 以下实例代码中, 后继section中重复部分限于篇幅省略处理**



首先统一\Generator接口, 屏蔽send会直接跳到第二次yield的问题,
内部隐式rewind, 需要先调用current() 获取当前value,
[参见send方法说明](http://php.net/manual/en/generator.send.php)



```php
<?php

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
```



然后让Generator可以正常迭代, 并且调用send方法将暂时将yield值作为yield表达式结果,

之所以这么做是因为之后的yield表达式可能是一个异步调用, 我们会把异步调用的结果send回Generator.

```
如, $ip = (yield async_dns_lookup(...)  );
     ^          |--------------------|
     |                  yield值
          |---------------------------|
                    yield 表达式
```

递归的执行next, 直到迭代器终止.


```php
<?php

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

```

PHP7支持Generator::getReturn, 可以通过return返回值,

我们在PHP5中使用Generator最后一次yield值作为返回值,

因为我们最终需要嵌套Generator的返回值.


```php
<?php

final class AsyncTask
{
    public function begin()
    {
        return $this->next();
    }

    // 添加return传递每一次迭代的结果, 直到向上传递到begin
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
```


PHP7Generator支持delegation, 可以自动展开yield antherGenerator,

我们需要在PHP5支持嵌套子生成器, 且支持将子生成器最后yield值作为yield表达式结果send回父生成器,

只需要加两行代码, 递归的产生一个AsyncTask对象来执行子生成器即可

```php
<?php

final class AsyncTask
{
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

```

异步化:
return其实可以被看成单参数, 且永远不会返回的函数(return :: r -> void),

将return解糖改写为函数参数continuation(CPS变换),

将Generator结果通过回调形式返回, 为引入异步迭代做准备.


```php
<?php

final class AsyncTask
{
    public $continuation;

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

function newGen()
{
    $r1 = (yield newSubGen());
    $r2 = (yield 2);
    echo $r1, $r2;
    yield 3;
}
$task = new AsyncTask(newGen());

$trace = function($r) { echo $r; };
$task->begin($trace); // output: 123

```


引入抽象的异步接口:

只有一个方法的接口通常都可以使用闭包代替, 不能替代的原因有interface会引入新类型, 闭包则不会.


```php
<?php

// 这里对异步模型进行抽象
interface Async
{
    public function begin(callable $callback);
}

// 经过CPS变换, AsyncTask符合Async定义, 实现该Async
final class AsyncTask implements Async
{
    public function next($result = null)
    {
        $value = $this->gen->send($result);

        if ($this->gen->valid()) {
            // \Generator -> Async
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


// 两个简单的例子
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
            // 这里我们会发现, 通过call $cc, 将返回值作为参数进行传递, 与callcc相像
            // $ip 通过$cc 从子生成器传入父生成器, 最终通过send方法成为yield表达式结果
            $cc($ip);
        });
    }
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

```


至此, 很少几行代码, 我们其实已经实现了异步迭代器的执行器;

下面先rollback回return的实现, 我们开始引入异常处理,

目标是在嵌套生成器之间正确向上抛出异常, 跨生成器捕获异常.


```php
<?php

// 为Gen引入throw方法
class Gen
{
    // PHP7 之前 关键词不能用作名字
    public function throw_(\Exception $ex)
    {
        return $this->generator->throw($ex);
    }
}

final class AsyncTask
{
    public function begin()
    {
        return $this->next();
    }

    // 这里添加第二个参数, 用来在迭代过程传递异常
    public function next($result = null, \Exception $ex = null)
    {
        if ($ex) {
            $this->gen->throw_($ex);
        }

        $ex = null;
        try {
            // send方法内部是一个resume的过程: 
            // 恢复execute_data上下文, 调用zend_execute_ex()继续执行,
            // 后续中op_array内才可能会抛出异常
            $value = $this->gen->send($result);
        } catch (\Exception $ex) {}

        if ($ex) {
            if ($this->gen->valid()) {
                // 传递异常
                return $this->next(null, $ex);
            } else {
                throw $ex;
            }
        } else {
            if ($this->gen->valid()) {
                // 正常yield值
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
```

重新处理生成器嵌套, 需要将子生成器异常抛向父生成器

```php
<?php

final class AsyncTask
{
    public function next($result = null, \Exception $ex = null)
    {
        try {
            if ($ex) {
                // c. 直接抛出异常
                // $ex来自子生成器, 调用父生成器throw抛出
                // 这里实现了 try { yield \Generator; } catch(\Exception $ex) { }
                // echo "c -> ";
                $value = $this->gen->throw_($ex);
            } else {
                // a2. 当前生成器可能抛出异常
                // echo "a2 -> ";
                $value = $this->gen->send($result);
            }

            if ($this->gen->valid()) {
                if ($value instanceof \Generator) {
                    // a3. 子生成器可能抛出异常
                    // echo "a3 -> ";
                    $value = (new self($value))->begin();
                }
                // echo "a4 -> ";
                return $this->next($value);
            } else {
                return $result;
            }
        } catch (\Exception $ex) {
            // !! 当生成器迭代过程发生未捕获异常, 生成器将会被关闭, valid()返回false,
            if ($this->gen->valid()) {
                // b1. 所以, 当前分支的异常一定不是当前生成器所抛出, 而是来自嵌套的子生成器
                // 此处将子生成器异常通过(c)向当前生成器抛出异常
                // echo "b1 -> ";
                return $this->next(null, $ex);
            } else {
                // b2. 逆向(递归栈帧)方向向上抛 或者 向父生成器(如果存在)抛出异常
                // echo "b2 -> ";
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

```


我们基于上述注释来观察异常传递流程:

```php
<?php

function g1()
{
    throw new \Exception();
    yield;
}
// a2 -> b2 ->
(new AsyncTask(g1()))->begin();


function g2()
{
    yield;
    throw new \Exception();
}
// a2 (-> a4 -> a2) -> b2 -> b2 ->
(new AsyncTask(g2()))->begin();


function g3()
{
    yield;
    throw new \Exception();
}
// a2 (-> a4 -> a2) -> b2 -> b2 ->
(new AsyncTask(g3()))->begin();


function g4()
{
    yield;
    yield;
    throw new \Exception();
}
// a2 (-> a4 -> a2) (-> a4 -> a2) -> b2 -> b2 -> b2 ->
(new AsyncTask(g4()))->begin();


function g5()
{
    throw new \Exception();
    /** @noinspection PhpUnreachableStatementInspection */
    yield;
}
function g7()
{
    yield g5();
}
// (a2 -> a3) -> a2 (-> b2 -> b1 -> c) -> b2 ->
(new AsyncTask(g7()))->begin();


function g6()
{
    yield;
    throw new \Exception();
}
function g8()
{
    yield g6();
}
// (a2 -> a3) -> a2 -> a4 -> a2 -> b2 -> b2 -> b1 -> c -> b2 ->
(new AsyncTask(g8()))->begin();


function g9()
{
    try {
        yield g5();
    } catch (\Exception $ex) {

    }
}
// a2 -> a3 -> a2 -> b2 -> b1 -> c ->
(new AsyncTask(g9()))->begin();
```

当生成器迭代过程发生未捕获异常, 生成器将会被关闭, valid()返回false,

未捕获异常会从生成器内部被抛向父作用域,

嵌套子生成器内部的未捕获异常必须最终被抛向根生成器的calling frame,

PHP7 中yield-from语言嵌套子生成器resume中异常传递实现采取goto try_again:标签方式层层向上抛出,

我们的代码因为递归迭代的原因, 未捕获异常需要逆递归栈帧方向层层上抛 , 性能方便有改进余地.

--------------

我们把加入异常处理的代码重新修改为CPS方式:

```php
<?php
final class AsyncTask
{
    public $continuation;

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
                    // 注意这里
                    $continuation = [$this, "next"];
                    (new self($value))->begin($continuation);
                } else {
                    $this->next($value);
                }
            } else {
                // 迭代结束 返回结果
                $cc = $this->continuation; // 父生成器next方法 或 用户传入continuation
                $cc($result, null);
            }
        } catch (\Exception $ex) {
            if ($this->gen->valid()) {
                // 抛出异常
                $this->next(null, $ex);
            } else {
                // 未捕获异常
                $cc = $this->continuation; // 父生成器next方法 或 用户传入continuation
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

```


重新加入Async抽象, 修改continuation的签名, 加入异常参数

continuation:: (mixed $r, \Exception $ex) -> void


```php
<?php
interface Async
{
    public function begin(callable $continuation);
}

final class AsyncTask implements Async
{
    public function next($result = null, $ex = null)
    {
        try {
            // ...
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
            // ...
        }
    }
}

$trace = function($r, $ex) {
    if ($ex instanceof \Exception) {
        echo "cc_ex:" . $ex->getMessage(), "\n";
    }
};

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
    $async = new AsyncException();
    yield $async;
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

// 内部try-catch异常
$task = new AsyncTask(newGen(true));
$task->begin($trace);
// output:
// catch:timeout
// 1

// 异常传递至AsyncTask的最终回调
$task = new AsyncTask(newGen(false));
$task->begin($trace);
// output:
// cc_ex:timeout
```

-----------------

Syscall与Context


按照nikic的思路引入与调度器内部交互的Syscall,

Syscall :: AsyncTask $task -> mixed

当将需要执行的函数打包成Syscall, 通过yield返回迭代器时,

可以从Syscall参数获取到当前迭代器对象, 这里提供了一个外界与AsyncTask交互的扩展点,

方便进行功能扩展, 我们借此演示如何添加跨生成器上下文.

加入Context的动机是在嵌套生成器共享数据, 解构生成器之间依赖,


```php
<?php

final class AsyncTask implements Async
{
    public $gen;
    public $continuation;
    public $parent;

    // 我们在构造器添加$parent参数, 把父子生成器链接起来, 使其可以进行回溯.
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
                if ($value instanceof Syscall) { // Syscall 签名见下方
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
```

我们现在来兑现刚开始的承诺, 出去接口声明外, 不到60行代码, 我们其实已经完成了功能完整,

支持`任务嵌套`与`异常处理`, 并可以通过Syscall扩充功能的半协程调度器.

接下来我们主要演示如何转换常用异步回调接口, 以及一些调度器的扩展功能.


```php
<?php

// Syscall将 callable :: AsyncTask $task -> mixed 包装成单独类型
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


// 因为PHP对象属性与数据均为Hashtable实现, 且恰巧生成器对象本身无任何属性,
// 我们这里把 我们把context kv数据附加到根生成器对象上
// 最终我们实现的 Context Get与Set函数
function getCtx($key, $default = null)
{
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



// Test
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
$task->begin($trace); // output: bar

```



为了易用性, 我们为AsyncTask的创建提供了一种灵活的参数传递的封装


```php
<?php

/**
 * spawn one semicoroutine
 *
 * @internal param callable|\Generator|mixed $task
 * @internal param callable $continuation function($r = null, $ex = null) {}
 * @internal param AsyncTask $parent
 * @internal param array $ctx Context可以附加在 \Generator 对象的属性上
 *
 *  第一个参数为task
 *  剩余参数无顺序要求
 *      如果参数类型 callable 则参数被设置为 Continuation
 *      如果参数类型 AsyncTask 则参数被设置为 ParentTask
 *      如果参数类型 array 则参数被设置为 Context
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

    $task = func_get_arg(0);
    $continuation = function() {};
    $parent = null;
    $ctx = [];

    parentTask, 
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
```



终于可以做一些有趣的事情了

异步回调API是无法直接使用yield语法的, 上文中我们将回调API显示实现Async接口,

通过参数传递异步结果回调度器, 我们似乎可以把这个简单的模式抽象出来, 实现一个穷人的call/cc


```php
<?php

// CallCC instanceof Async
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

function callcc(callable $fn)
{
    return new CallCC($fn);
}
```

我们创造的半协程中的callcc的功能有限, yield只能将控制权从Generator转移到起caller中:

[wiki:Coroutine](https://en.wikipedia.org/wiki/Coroutine)
> Generators, also known as semicoroutines, are also a generalisation of subroutines, but are more limited than coroutines. Specifically, while both of these can yield multiple times, suspending their execution and allowing re-entry at multiple entry points, they differ in that coroutines can control where execution continues after they yield, while generators cannot, instead transferring control back to the generator's caller. That is, since generators are primarily used to simplify the writing of iterators, the yield statement in a generator does not specify a coroutine to jump to, but rather passes a value back to a parent routine.


我认为这里我们引入的semi-callcc实际上是人肉进行的thunky,

因为swoole的异步api数量不多, 我们正好来看例子:

# asyncInvoke :: (...$args, callable :: (mixed $r, Exception $ex) -> void) -> void
# syncInvoke :: ...$args -> (callable :: (mixed $r, Exception $ex) -> void)


```php
<?php

function async_sleep($ms)
{
    return callcc(function($k) use($ms) {
        swoole_timer_after($ms, function() use($k) {
            $k(null);
        });
    });
}

function async_dns_lookup($host)
{
    return callcc(function($k) use($host) {
        swoole_async_dns_lookup($host, function($host, $ip) use($k) {
            $k($ip);
        });
    });
}

class HttpClient extends \swoole_http_client
{
    public function awaitGet($uri)
    {
        return callcc(function($k) use($uri) {
            $this->get($uri, $k);
        });
    }

    public function awaitPost($uri, $post)
    {
        return callcc(function($k) use($uri, $post) {
            $this->post($uri, $post, $k);
        });
    }

    public function awaitExecute($uri)
    {
        return callcc(function($k) use($uri) {
            $this->execute($uri, $k);
        });
    }
}


// 这里!
spawn(function() {
    $ip = (yield async_dns_lookup("www.baidu.com"));
    $cli = new HttpClient($ip, 80);
    $cli->setHeaders(["foo" => "bar"]);
    $cli = (yield $cli->awaitGet("/"));
    echo $cli->body, "\n";
});

```

我们可以用相同的模式来封装swoole的剩余异步api, 比如TcpClient,MysqlClient,RedisClient

大家可以举一反三;

(推荐继承swoole原生类, 而不是直接实现Async)


---------


看到这里, 你可能已经注意到我们上面接口的问题了: 没有任何超时处理

通常情况我们需要为每个异步添加定时器, 回调成功取消定时器, 否则在定时器回调透传异常;


```php
<?php

// helper function
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

// helper function
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


// 我们的dns查询有了超时透传异常的能力了
function async_dns_lookup($host, $timeout = 100)
{
    return callcc(function($k) use($host) {
        swoole_async_dns_lookup($host, function($host, $ip) use($k) {
            $k($ip);
        });
    }, $timeout);
}


spawn(function() {
    try {
        yield async_dns_lookup("www.xxx.com", 1);
    } catch (\Exception $ex) {
        echo $ex; // ex!
    }
});

```

但是, 我们可以有更优雅的方式来超时处理


```php
<?php

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


// helper function
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
```

事实上我们实现了一个Promise.race的接口


```php
<?php

// 我们重新来看这个简单dns查询函数
function async_dns_lookup($host)
{
    return callcc(function($k) use($host) {
        swoole_async_dns_lookup($host, function($host, $ip) use($k) {
            $k($ip);
        });
    });
}

// 我们有了一个纯粹的超时透传异常的函数
function timeout($ms)
{
    return callcc(function($k) use($ms) {
        swoole_timer_after($ms, function() use($k) {
            $k(null, new \Exception("timeout"));
        });
    });
}

// 当我们采取race语义并发执行dns查询与超时异常函数
// 其实我们构造了一个更为灵活的超时处理方案
spawn(function() {
    try {
        $ip = (yield race([
            async_dns_lookup("www.baidu.com"),
            timeout(100),
        ]));

        $res = (yield race([
            (new HttpClient($ip, 80))->awaitGet("/"),
            timeout(200),
        ]));
        var_dump($res->statusCode);
    } catch (\Exception $ex) {
        echo $ex;
        swoole_event_exit();
    }
});
```


我们非常容易构造出更多的超时的接口, 但我们代码看起来比之前更清晰了

```php
<?php

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


// test
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

```


有了Any, 我们自然想到实现All,

Any 表示多个异步回调, 任意回调执行则任务完成, All 则表示等待所有回调执行


```php
<?php

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


// test
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


我们实现了出与Promise.all接口, 

或者更复杂一些, 我们也可以实现拥有并发窗口调控能力的chunk方式作业的接口;

------------------------------------------------------------------
------------------------------------------------------------------

我们已经拥有了 `spawn` `callcc` `race` `all` `timeout` 已经支持yield的常用Client实现,

------------------------------------------------------------------
------------------------------------------------------------------


虽然我们已经构建了基于yield语义的半协程, 事实上, 我们仍旧可以做一些更有趣的事情,

比如Channel, 没错,就是golang的channel.

[playground](https://tour.golang.org/concurrency/2)

> By default, sends and receives block until the other side is ready.

> This allows goroutines to synchronize without explicit locks or condition variables.


相比较golang, 我们只有一个线程, 对于chan发送与接收的阻塞的处理, 

我们最终转换为对使用chan的协程的控制流的控制.

我们首先实现无缓存的Channel:


```php
<?php

class Channel
{
    // 因为同一个channel可能有多个接收者,使用队列实现,保证调度均衡
    // 队列内保存的是被阻塞的接收者协程的控制流,即call/cc的参数,我们模拟的continuation
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
                
                // 当chan没有接收者,发送者协程挂起(将$cc入列,不调用$cc回送数据)
                $this->sendQ->enqueue([$cc, $val]);

            } else {

                // 当chan对端有接收者,将挂起接收者协程出列,
                // 调用接收者$recvCc发送数据,运行接收者协程后继代码
                // 执行完毕或者遇到Async挂起,$recvCc()调用返回,
                // 调用$cc(),控制流回到发送者协程
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

                // 当chan没有发送者,接收者协程挂起（将$cc入列）
                $this->recvQ->enqueue($cc);

            } else {

                // 当chan对端有发送者,将挂起发送者协程与待发送数据出列
                // 调用发送者$sendCc发送数据,运行发送者协程后继代码
                // 执行完毕或者遇到Async挂起,$sendCc()调用返回,
                // 调用$cc(),控制流回到接收者协程
                list($sendCc, $val) = $this->sendQ->dequeue();
                $sendCc(null, null);
                $cc($val, null);

            }
        });
    }
}
```

接下来我们来实现带缓存的Channel

> Sends to a buffered channel block only when the buffer is full. 

> Receives block when the buffer is empty.

```php
<?php

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

                // 当无数据可接收时, $cc入列,让出控制流,挂起接收者协程
                $this->recvCc->enqueue($cc);

            } else {

                // 当有数据可接收时, 先接收数据,然后恢复控制流
                $val = $this->queue->dequeue();
                $this->cap++;
                $cc($val, null);

            }

            // 递归的唤醒其他被阻塞的发送者与接收者收发数据,注意顺序
            $this->recvPingPong();
        });
    }

    public function send($val)
    {
        return callcc(function($cc) use($val) {
            if ($this->cap > 0) {

                // 当缓存未满,发送数据直接加入缓存,然后恢复控制流
                $this->queue->enqueue($val);
                $this->cap--;
                $cc(null, null);

            } else {

                // 当缓存满,发送者控制流与发送数据入列,让出控制流,挂起发送者协程
                $this->sendCc->enqueue([$cc, $val]);

            }

            // 递归的唤醒其他被阻塞的接收者与发送者收发数据,注意顺序
            $this->sendPingPong();

            // 如果全部代码都为同步,防止多个发送者时, 数据全部来自某个发送者
            // 应该把sendPingPong 修改为异步执行 defer([$this, "sendPingPong"]);
            // 但是swoole本身的defer实现有bug,除非把defer 实现为swoole_timer_after(1, ...)
            // recvPingPong 同理
        });
    }

    public function recvPingPong()
    {
        // 当有阻塞的发送者,唤醒其发送数据
        if (!$this->sendCc->isEmpty() && $this->cap > 0) {
            list($sendCc, $val) = $this->sendCc->dequeue();
            $this->queue->enqueue($val);
            $this->cap--;
            $sendCc(null, null);

            // 当有阻塞的接收者,唤醒其接收数据
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
        // 当有阻塞的接收者,唤醒其接收数据
        if (!$this->recvCc->isEmpty() && !$this->queue->isEmpty()) {
            $recvCc = $this->recvCc->dequeue();
            $val = $this->queue->dequeue();
            $this->cap++;
            $recvCc($val);

            // 当有阻塞的发送者,唤醒其发送数据
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
```



这是我们最终得到的接口


```php
<?php

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
```


第一个典型例子, PINGPONG

与golang的channel类似, 我们可以在两个semicoroutine之间做同步


```php
<?php

// 构建两个单向channel, 我们只单向收发数据

$pingCh = chan();
$pongCh = chan();

go(function() use($pingCh, $pongCh) {
    while (true) {
        echo (yield $pingCh->recv());
        yield $pongCh->send("PONG\n");

        // 递归调度器实现, 需要引入异步的方法退栈, 否则Stack Overflow...
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

```


当然, 我们可以很轻易构建一个生产者-消费者模型


```php
<?php

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

```

chan 自身是first-class, 所以可传递


```php
<?php

// 我们通过一个chan来发送另一个chan
// 然后等待接收到这个chan的semicoroutine回送数据

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
```

我们通过控制channel缓存大小 观察输出结果

```php
<?php
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

```


一个更具体的生产者消费者的例子:


```php
<?php

// 缓存两个结果
$ch = chan(2);

// 从channel接口请求写过写文件
go(function() use($ch) {
    $file = new AsyncFile("path/to/save");
    while (true) {
        list($host, $status) = (yield $ch->recv());
        yield $file->write("$host: $status\n");
    }
});

// 请求并写入chan
go(function() use($ch) {
    while (true) {
        $host = "www.baidu.com";
        $resp = (yield async_curl_get($host));
        yield $ch->send([$host, $resp->statusCode]);
    }
});

// 请求并写入chan
go(function() use($ch) {
    while (true) {
        $host = "www.bing.com";
        $resp = (yield async_curl_get($host));
        yield $ch->send([$host, $resp->statusCode]);
    }
});

// output:
```


channel的发送与接受没有超时机制, golang可以select多个chan实现超时处理,

我们也可以做一个range的设施, 或者在send于recv接受直接添加超时参数, 扩展接口功能,

留待读者自行实现. 



-----------------------------------------------------------------------------------------------
-----------------------------------------------------------------------------------------------
-----------------------------------------------------------------------------------------------
-----------------------------------------------------------------------------------------------
-----------------------------------------------------------------------------------------------




### Koa

Koa自述是下一代web框架：

> 由 Express 原班人马打造的 koa,致力于成为一个更小、更健壮、更富有表现力的 Web 框架。
> 使用 koa 编写 web 应用,通过组合不同的 generator,
> 可以免除重复繁琐的回调函数嵌套,并极大地提升常用错误处理效率。
> Koa 不在内核方法中绑定任何中间件,它仅仅提供了一个轻量优雅的函数库,使得编写 Web 应用变得得心应手。


Koa是那种与martini一样,设计清爽的框架,
我们可以用少量的代码基于PHP5.6与yz-swoole(有赞内部自研稳定版本的Swoole,暂且等待,即将发布)重新发明；
martini与Koa都属于中间件web框架,采用洋葱模型middleware stack,自身没有提供任何业务相关库,但提供了强大的灵活性,

对于web应用来讲,所有业务逻辑归根结底都在处理请求与相应对象,
web中间件实质就是在请求与响应中间开放出来的可编排的扩展点,
比如在修改请求做URLRewrite,比如身份验证,安全拦截；

真正的业务逻辑都可以通过middleware实现,或者说按特定顺序对中间件灵活组合编排;
Koa对中间编写的贡献是,合并req + res对象,封装组合方式,让编写更直观方便；
因为koa2.x决定全面使用async/await, 我们这里使用PHP实现koa1.x,
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
// 对于web应用来讲,中间件在请求与响应中间开放出来可编排的扩展点,改写req res


// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

// 演示koa的中间件之前,我们先来一场穿越地心之旅

// https://zh.wikipedia.org/wiki/%E5%9C%B0%E7%90%83%E6%A7%8B%E9%80%A0
// 物理学上,地球可划分为岩石圈、软流层、地幔、外核和内核5层。
// 化学上,地球被划分为地壳、上地幔、下地幔、外核和内核。地质学上对地球各层的划分


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
        // 我们到达内核之前遭遇了岩浆,计划终止,等待救援
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
        // 因为温度过高,去程匆匆离开外壳
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
// 抑或,我们也可以放弃after,函数则成为 前置过滤器(Filter),如果我们放弃before,函数则成为 后置过滤器(Terminator)
// 关于错误处理, 我们可以在某层的函数 try-catch next调用, 从而阻止内层函数的异常向上传递
// 想想我们在地底深处包裹了一层可以抵御岩浆外太空物质, 岩浆被安全的舒服到了地心
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
// koa 最大的优势是中间件系统,比传统的做法多了一层逆序回调,像我们之前展示的case,
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


// Context 组件代理了Request与Response中的方法和属性,简化了使用方式与中间件接口
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
// 这里插入一点,中间件的use顺序非常重要,比如这里的异常,必须优先use,才可以捕获下层中间件
// 抛出的异常,又比如session中间件,需要优先于业务处理中间件
// 而像处理404状态码的中间件,也需要高优先级,但是逻辑只会在upstream逆序调用,即next之后

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

// 为了更好阻止代码,我们为Middleware声明一个接口

interface Middleware
{
    public function __invoke(Context $ctx, $next);
}

// 实现该接口的对象本身满足 callable 类型,我们的中间件接受任何callable
// 可以是 function, Closure, array 等

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

// 路由是httpServer必不可少的组件,我们考虑如何实现一个路由中间件

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