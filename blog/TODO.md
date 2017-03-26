申请一个新的gitlab账户, 防止Koa项目

最后谈一次 JavaScript 异步编程
https://zhuanlan.zhihu.com/p/24444262

Coroutine从入门到劝退
https://zhuanlan.zhihu.com/p/25513336


引入
compose 异步序列
https://zhuanlan.zhihu.com/p/24444262


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
