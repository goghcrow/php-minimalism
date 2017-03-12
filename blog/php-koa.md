必须阅读且深入理解：
https://zhuanlan.zhihu.com/p/21391101
https://zhuanlan.zhihu.com/p/25513336
http://www.ruanyifeng.com/blog/2016/09/redux_tutorial_part_two_async_operations.html




http://koajs.com/#introduction
http://koa.rednode.cn/
http://koajs.com/
http://www.ruanyifeng.com/blog/2015/04/generator.html
https://github.com/senchalabs/connect/

Coroutine从入门到劝退
https://zhuanlan.zhihu.com/p/25513336


最后谈一次 JavaScript 异步编程
https://zhuanlan.zhihu.com/p/24444262




### yield 可以理解成为CPS变换语法糖 !!!!!
`任何异步编程模型，最终都是基于异步回调的`
`异步回调本质上是 Continuation`
`异步回调是手写的CPS程序`
“回调”是“异步”的精髓，无论是何种异步模型，
最终都是基于回调函数的，最终其实也可以归纳成这里的异步调用形式。
并没有消除Callback
实质 Continuation Monad
Generator async/await 消除Callback
Continuation Monad




koa风生水起, 用php + yz_swoole实现一个KOA
1. AsyncTask 实现
2. race timeout 实现
3. callcc实现， 转换dns查询, 转换timer
4. 各种客户端转换
4. render实现
5. 错误处理
6. router实现， 介绍为php-koa实现路有时候的示例：参考martini
    1. 一个HTTP方法配对一个URL匹配模型
    2. 正则路由
    3. 参数列表
    4. group路由分组
7. assic 画洋葱圈模型
8. 实现一堆中间件
9. 中间件的顺序有讲究
use: 中间件处理器与路由处理器接口一致
martini.Context.Next()
大量中间件资源


koa中间件
https://www.npmjs.com/package/koa-middleware
https://github.com/koajs/ratelimit/blob/master/index.js
https://github.com/koajs
http://javascript.ruanyifeng.com/nodejs/koa.html
https://github.com/tj


Koa 下一代web框架
> 由 Express 原班人马打造的 koa，致力于成为一个更小、更健壮、更富有表现力的 Web 框架。
> 使用 koa 编写 web 应用，通过组合不同的 generator，
> 可以免除重复繁琐的回调函数嵌套，并极大地提升常用错误处理效率。
> Koa 不在内核方法中绑定任何中间件，它仅仅提供了一个轻量优雅的函数库，使得编写 Web 应用变得得心应手。


2012年golang1.0的release，一种古老的技术被重新发明出来，面对大并发编程，
不同于除了回调模型，协程模型映入眼帘，高冷的erlang于是有了分庭抗礼的对手，抢占、非抢占式调度之争成了热门论题,
这期间node社区如雨后春笋冒了出来，大家乐此不彼的做了各种"测试".广大屌丝PHPer程序员终于坐不住了, Swoole横空出世.
在此感谢swoole以及libevent等扩展让我们广大PHPer体验了一把异步编程，


Javascript与语言特性与NodeApi接口均是回调形式，回调形式固有的问题与对程序员的不友好性亟待解决，
从老赵脑洞大开编译方案windjs到Promise，各种方案层出不穷，最终Promise（MDN 文档）被采纳为官方「异步编程标准规范」;
进一步结合yield语义，大名鼎鼎的co库实现了"同步"书写异步代码，与老赵的方案殊途同归，最后，官方采用了
这种很早出现在C#等语言的async/await关键词，将yield内化为一种语法糖；
Callback -> Promise -> Generator -> async/await;


因为模型原因,Swoole中关键接口同样全部是回调形式，PHPer"有幸"在高并发这个命题上遭遇与Node一样的问题，
去年我司开源[Zan](http://zanphp.io/)，已经算解决了这一问题，但是程序员少不了造轮子的"天性"，
且Koa是那种与go-martini一样，设计出彩
让人眼前一亮，一见钟情的性感框架，遂决定用PHP+yz-swoole重新发明(抄袭)；
(yz-swoole:有赞内部稳定版本的Swoole)
个人把go-martini与Koa都归类为中间件框架，自身短小精悍，只提供小而美（简陋）的函数库，
没有传统的Controller与Action概念，但采用洋葱模型middleware stack，提供了强大的灵活性，
基本上所有功能功能都要通过middleware实现，使用者需要像搭积木般对各种中间件灵活组合，
这也许是framework的真正内涵；
（对laravel不熟悉，捂脸）




koa类似中间件系统：
Ruby's Rack
redux 中间件
matini 中间件，灵感来源express的中间件Connect
koa中间件，更方便编写，合并req， res -> ctx

对于web应用来讲，个人对这种中间件的理解是在请求与相应中间开放出来可编排的扩展点，
改写req res

Koa 的appl对象核心是编排好的middleware对象，

request与response穿透栈式的结构，
> 是一个包含一系列中间件 generator 函数的对象。 
> 这些中间件函数基于 request 请求以一个类似于栈的结构组成并依次执行。 
> 然而 Koa 的核心设计思路是为中间件层提供高级语法糖封装，以增强其互用性和健壮性，并使得编写中间件变得相当有趣。






nodejs风靡多半是因为js语言的函数式特性适合异步代码的编写；
PHP甚至没有词法作用域，自然也没有真正的词法闭包, \Closure对象朴实的采用了use这一丑陋的语法来显式捕获UpValue为类属性（自黑捂脸），
但总归形式上我们有了First Class的这一基础设施，语法甜蜜度上升；
2013年PHP社区Nikita给出了GeneratorsRFC的完整实现并随着php5.5一起发布，我们离优雅的异步编程又进了一步；
参见Nikita同期文章的译文，以及有关Generator，不多介绍，自行参考 http://www.laruence.com/2015/05/28/3038.html



谈及Koa首先要谈及co库，鼎鼎大名的co库实质是Generator自动执行器;
在异步模型中，我们主要利用yield语义操纵程序控制流，异步api+yield成为"异步迭代器"，
co的工作则是自动执行异步迭代器，异步接口yield退出，利用回调驱动迭代器前进，
最终实现了半协程, 同步书写,异步执行;


`任何异步编程模型，最终都是基于异步回调的`
`异步回调本质上是 Continuation`
`异步回调是手写的CPS程序`
`yield 可以理解成为CPS变换语法糖`
`yield并没有消除Callback`
`实质 Continuation Monad`
`Generator async/await 消除Callback`
`Continuation Monad`


Koa的核心在于middleware的组合，Generator是最重要的middleware形式，核心是通过组合middleware(generator函数)实现了最大的灵活性;
koa1.x高度依赖Generator与co，koa2.x决定全面使用async/await，
由于php应用基本都是同步模型，语言本身没有太大的对于异步语法糖的需求，所以在可见的未来不会出现对async/await的需要，
我们的目标是基于Generator来打造php-koa，





我们首先来实现koa的基础设施，co库，ZanPHP内部实现有一套半协程调度器，个人觉得可以更精练，这里使用50行左右代码来重新实现一个(造轮子，捂脸)，


无论何「异步模型」，最终都是基于回调的，最后都会抽象出一种通用的「异步任务模型」，异步调用形式，异步api,
'异步'的精神内核是回调，
虽然Promise是一套比较完善的方案，但是如何实现Promise本身超出本文范畴，（感兴趣同学可以参见ReactPromisePHP） 
且PHP没有异步接口的历史包袱，没有旧的类库，所幸我们采用一种最为简单有效的异步任务模型，采用如何接口来抽象异步任务（异步API）

async :: ((result, exception) -> void) -> void


```php
<?php 
interface Async
{
    /**
     * @param callable $callback 
     *  void($result = null, \Exception $ex = null)
     * @return void 
     */
    public function begin(callable $callback);
}
```




50行代码实现半协程调度器,特性...
抽象AsyncTask
代码

测试

compose 函数

Application 函数


级联









------------------------------------------

如何评价 Node.js 的koa框架？
https://www.zhihu.com/question/25388201


传统web框架的中间件均会提供 (request, response)两个参数;
koa将此合二为一 (ctx)

bind(Context), $this语法糖



yield 或者 async/await方案均可以让代码清晰可读;

koa比较特色的地方是对中间件的compose方式

yield 可以实现

异步回调函数需要使用thunk或者其他工具进行转换
这里提供一种callcc的工具函数
实际上与Scheme提供的设施能力要弱;


Koa中间件Cascading级联式结构 
由上层中间件负责调用下层

中间件参数约束


------------------------------------------






yield 协程
swoole 

「NonBlocking I/O」非异步I/O
callback 形式异步编程模型









```php
<?php
/**
 * CPS: Interface Async
 * @package Minimalism\A\Core
 */
interface Async
{
    /**
     * 开启异步任务，立即返回，任务完成回调$continuation
     * @param callable $continuation
     *      void(mixed $result = null, \Throwable|\Exception $ex = null)
     * @return void
     */
    public function start(callable $continuation);
}
```

```php
<?php
final class AsyncTask implements Async
{
    private $isfirst = true;

    public $parent;
    public $generator;
    public $continuation;

    /**
     * AsyncTask constructor.
     * @param \Generator $generator
     * @param AsyncTask|null $parent
     */
    public function __construct(\Generator $generator, AsyncTask $parent = null)
    {
        $this->generator = $generator;
        $this->parent = $parent;
    }

    /**
     * @param callable|null $continuation function($r, $ex = null) { }
     */
    public function start(callable $continuation = null)
    {
        $this->continuation = $continuation;
        $this->next();
    }

    /**
     * @param mixed|null $result
     * @param \Throwable|\Exception|null $ex
     * @internal
     */
    public function next($result = null, $ex = null)
    {
        if ($ex instanceof CancelTaskException || !$this->generator->valid()) {
            goto continuation;
        }

        try {
            if ($ex) {
                $value = $this->generator->throw($ex);
                $ex = null;
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
                    $value = new self($value, $this);
                }

                if ($value instanceof Async) {
                    $value->begin([$this, "next"]);
                } else {
                    $this->next($value, null);
                }
            } else {
                continuation:
                if ($continuation = $this->continuation) {
                    $continuation($result, $ex);
                }
            }
        } catch (\Throwable $t) {
            $this->next(null, $t);
        } catch (\Exception $ex) {
            $this->next(null, $ex);
        }
    }
}
```

TODO 示例代码
修改图 ...

```
A middleware1 开始
C middleware2 开始
E middleware3 开始
======= G =======
F middleware3 结束
D middleware2 结束
B middleware1 结束
```

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






koa readme !!!

koa是中间件框架,中间件可以用callable与Generator表示
参数约定 ctx, next


+The `Context` object also provides shortcuts for methods on its `request` and `response`.  In the prior
+examples,  `ctx.type` can be used instead of `ctx.request.type` and `ctx.accepts` can be used
+instead of `ctx.request.accepts`.


+Each middleware receives a Koa `Context` object that encapsulates an incoming
+http message and the corresponding response to that message.  `ctx` is often used
+as the parameter name for the context object.



## Koa Application
+
+The object created when executing `new Koa()` is known as the Koa application object.
+
+The application object is Koa's interface with node's http server and handles the registration
+of middleware, dispatching to the middleware from http, default error handling, as well as
+configuration of the context, request and response objects.




node 设计模式

1. FP compose
2. java web 责任链 拦截器 过滤器
3. Middlewares/ pipelines

```
app.use = function(fn){  
  this.middleware.push(fn);
  return this;
};

var i = middleware.length;  
while (i--) {  
  next = middleware[i].call(this, next);
}
```


```

function m1($next)
{
    return function() use($next) {
        echo "before m1","\n";
        $next();
        echo "after m1","\n";
    };
}

function m2($next)
{
    return function() use($next) {
        echo "before m2", "\n";
        $next();
        echo "after m2", "\n";
    };
}

function m3($next)
{
    return function() use($next) {
        echo "before m3", "\n";
        $next();
        echo "after m3", "\n";
    };
}

function compose1(...$fns)
{
    $next = function() { echo "core\n"; };
    $i = count($fns);
    while ($i--) {
        $next = $fns[$i]($next);
    }
    return $next;
}

$ns = __NAMESPACE__;
$fn  = compose1("$ns\\m1", "$ns\\m2", "$ns\\m3");
$fn();

```

https://llh911001.gitbooks.io/mostly-adequate-guide-chinese/content/ch5.html#函数饲养
