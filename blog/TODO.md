最后谈一次 JavaScript 异步编程
https://zhuanlan.zhihu.com/p/24444262

Coroutine从入门到劝退
https://zhuanlan.zhihu.com/p/25513336

引入
compose 异步序列
https://zhuanlan.zhihu.com/p/24444262


什么程序设计语言机制是处理异步 IO 最恰当的抽象？
https://www.zhihu.com/question/19585576




Promise与C# Tasks.Task 是一类东西, 且功能远没有Task接口丰富

Belleve
> callcc 是功能集最小的。
> 程序员最容易接受的是 coroutine。

TODO//
channel 添加 close 接口

Monad是用来表达序列执行的一种构造.
所以，Monad 代表的是层次，而不是顺序。（回想下 CPS，是不是用层次表示顺序的？）
回调是用层次表示顺序.


Go中channel机制介绍
语法
在Go中，channel结构是Goroutine间消息传递的基础，属于基本类型，在runtime库中以C语言实现。 Go中针对channel的操作主要包括以下几种：
创建：ch = make(chan int, N)
发送：ch <- l
接收：l <- ch
关闭：close(ch)
另外，还可以通过select语句同时在多个channel上进行收发操作，语法如下：
select {
  case ch01 <- x:
      ... ... /* do something ... */
  case y <- ch02:
      ... ... /* do something ... */
  default:
      ... ... /* no event ... */
}
此外，基于select的操作tai还支持超时控制，具体的语法示例如下：
select {
  case v := <- ch:
      ... ...
  case <- time.After(5 * time.Second):
      ... ...
}





TODO//
FibJS的作者，响马所言，

编程范式的改变，需要一整套解决方案，包括协程引擎，调度器，重新封装的 api，仅靠一个核心引擎很难改变。
generator 可以从形式上面同步化逻辑，但是入口和出口仍是异步，需要每个人都小心翼翼的先理解异步，再去写同步。
二者都很难改变 nodejs 实质异步的门槛。



http://www.ruanyifeng.com/blog/2015/04/generator.html


不过好在ES2015发布了新的语法特性yield,也带来了异步抽象——Promise结构,但基于生成器(generator)实现的特性会略微降低Node.js原有的性能而无法两全其美,

受限于JavaScript解释性语言的性质,使得其无法在不借助外部编译器,自行完成CPS变换。

> `无论何「异步模型」,最终都是基于回调的,最后都会抽象出一种通用的「异步任务模型」,'异步'的精神内核是回调`
> `任何异步编程模型,最终都是基于异步回调的`
> `异步回调本质上是 Continuation`
> `异步回调是手写的CPS程序`
> `实质 Continuation Monad`


Fork仅需要调用一次。
调用时会一直同步执行到第一个yield为止,然后Fork返回,上层不会阻塞在Fork调用。
之后由底层做调度,保证resume回来的时候仍然由主线程继续执行yield后面的语句,直到遇到下个yield为止。
循环往复,直到作为Fork参数的coroutine执行完毕。





4. 各种客户端转换
4. render实现
use: 中间件处理器与路由处理器接口一致
大量中间件资源


------------------------------------------


输出结构演示了这种洋葱圈结构的实现方式与执行流程


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





Koa中间件
https://www.npmjs.com/package/Koa-middleware
https://github.com/Koajs/ratelimit/blob/master/index.js
https://github.com/Koajs
http://javascript.ruanyifeng.com/nodejs/Koa.html
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
