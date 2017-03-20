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



> 但随之而来的弊病显然也是罄竹难书——callback hell，导致人类线性的思维会被拆分，业务逻辑会被放置在各个回调函数中，调试和维护的难度陡然增加。不过好在ES2015发布了新的语法特性yield，也带来了异步抽象——Promise结构，但基于生成器(generator)实现的特性会略微降低Node.js原有的性能而无法两全其美，受限于JavaScript解释性语言的性质，使得其无法在不借助外部编译器，自行完成CPS变换。而scala则可以基于宏来实现这一功能，将原有的代码以同步的形式编写，但最终被编译成回调形式的代码，一来编码时逻辑得以线性的顺序组织，二来没有上下文切换的开销，两全其美。




> 无论何「异步模型」，最终都是基于回调的，最后都会抽象出一种通用的「异步任务模型」,'异步'的精神内核是回调，
> `任何异步编程模型，最终都是基于异步回调的`
> `异步回调本质上是 Continuation`
> `异步回调是手写的CPS程序`
> `yield 可以理解成为CPS变换语法糖`
> `yield并没有消除Callback`
> `实质 Continuation Monad`
> `Generator async/await 消除Callback`
> `Continuation Monad`



`yield语义从抽象角度可以理解为CPS变换语法糖`
`yield语义从控制流角度可以理解为将控制权从generator(callee)转义到caller`
`借由底层eventloop，在事件回调中异步驱动generator，将控制权重新转移回generator`



`而从实现角度来看，
Generator对象都会有自己的zend_execute_data与zend_vm_stack，
每次yield

每次调用send next throw方法，resume执行，都需要首先备份EG中相关上下文，
然后将Generator的execute_data信息恢复到EG，
调用zend_execute_ex()执行从当前上下文恢复执行执行，最后恢复执行前EG信息
`




`任何异步编程模型，最终都是基于异步回调的`
`异步回调本质上是 Continuation`
`异步回调是手写的CPS程序`
`回调”是“异步”的精髓，无论是何种异步模型`
`最终都是基于回调函数的，最终其实也可以归纳成这里的异步调用形式`
`并没有消除Callback`
`实质 Continuation Monad`
`Generator async/await 消除Callback`




[Coroutine](https://en.wikipedia.org/wiki/Coroutine)
Generators, also known as semicoroutines, are also a generalisation of subroutines, but are more limited than coroutines. Specifically, while both of these can yield multiple times, suspending their execution and allowing re-entry at multiple entry points, they differ in that coroutines can control where execution continues after they yield, while generators cannot, instead transferring control back to the generator's caller. That is, since generators are primarily used to simplify the writing of iterators, the yield statement in a generator does not specify a coroutine to jump to, but rather passes a value back to a parent routine.

However, it is still possible to implement coroutines on top of a generator facility, with the aid of a top-level dispatcher routine (a trampoline, essentially) that passes control explicitly to child generators identified by tokens passed back from the generators:




Fork仅需要调用一次。
调用时会一直同步执行到第一个yield为止，然后Fork返回，上层不会阻塞在Fork调用。
之后由底层做调度，保证resume回来的时候仍然由主线程继续执行yield后面的语句，直到遇到下个yield为止。
循环往复，直到作为Fork参数的coroutine执行完毕。




IO是相比CPU内存是最慢的，高性能Server最重要的处理好IO，IO模型与编程模型息息相关;

""coroutine 实现的核心问题是控制流转换，""
我们可以相对容易的实现一个半协程，归功于
zend vm中保存stack与execute_data的工作已经由Generator实现了




C#很早就支持了async/await, node的async/await支持也姗姗来迟, 
PHP生态以同步模型为主, 没有原生的异步语义支持, 同步方式书写异步逻辑只能借助yield+coroutine完成;

coroutine如果从流程上理解是一种特殊的控制流,
caller 
callee 出让控制流
call/cc


fork()

协程管理器;

all
* 多协程的调度机制
* 协程间通信机制










Koa风生水起, 用php + yz_swoole实现一个KOA

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








下面让我们一步一步实现一个全功能的异步任务执行器，或者更高大尚的名字协程调度器；


首先, 我们来分析co的接口


```php
<?php
co(function() {
    yield get("http://www.google.com");
});
```

```php
<?php

```

首先, 因为我们的对\Generator进行异步迭代
 自身也是异步执行, 需要实现Async
语义上接近 简陋的版本 Promise.then 与 Promise.catch



首先让我们实现\Generator自动执行，我知道foreach可以，但我们需要更精确地控制





TODO 修改
所谓 Thunk 化就是将多参数函数，将其替换成单参数只接受回调函数作为唯一参数的版本 




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
            ---------------------------------------
            |             middleware1             |
            |    ----------------------------     |
            |    |        middleware2        |    |
            |    |    -------------------    |    |
            |    |    |   middleware3   |    |    |
            |    |    |                 |    |    |
            |    |    |  —————————————  |    |    |
request ——————————————>  |           |   —————————---> response
            |    |    |  |     G     |  |    |    |
            | A  | C  | E ——————————— F |  D |  B |
            |    |    |                 |    |    |
            |    |    -------------------    |    |
            |    |                           |    |
            |    ----------------------------     |
            |                                     |
            --------------------------------------


顺序 A -> C -> E -> G -> F -> D -> B
    \---------------/   \----------/
            ↓                ↓
        请求响应完毕        收尾工作
```








node 设计模式

1. FP compose
2. java web 责任链 拦截器 过滤器
3. Middlewares/ pipelines



------------------------------------------------------------------------------
------------------------------------------------------------------------------
------------------------------------------------------------------------------
------------------------------------------------------------------------------
------------------------------------------------------------------------------


koa类似中间件系统：
Ruby's Rack
redux 中间件
matini 中间件，灵感来源express的中间件Connect



koa中间件
https://www.npmjs.com/package/koa-middleware
https://github.com/koajs/ratelimit/blob/master/index.js
https://github.com/koajs
http://javascript.ruanyifeng.com/nodejs/koa.html
https://github.com/tj



关于中间件

Rails 中使用的 rack middleware stack:
cd to/your/rails/project/path
rake middleware
得到的内容如下:

use Rack::Sendfile
use ActionDispatch::Static
use Rack::Lock
use #<ActiveSupport::Cache::Strategy::LocalCache::Middleware:0x000000029a0838>
use Rack::Runtime
use Rack::MethodOverride
use ActionDispatch::RequestId
use Rails::Rack::Logger
use ActionDispatch::ShowExceptions
use ActionDispatch::DebugExceptions
use ActionDispatch::RemoteIp
use ActionDispatch::Reloader
use ActionDispatch::Callbacks
use ActiveRecord::Migration::CheckPending
use ActiveRecord::ConnectionAdapters::ConnectionManagement
use ActiveRecord::QueryCache
use ActionDispatch::Cookies
use ActionDispatch::Session::CookieStore
use ActionDispatch::Flash
use ActionDispatch::ParamsParser
use Rack::Head
use Rack::ConditionalGet
use Rack::ETag
run Rails.application.routes