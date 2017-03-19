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

// 但凡Web框架都会有基于Aop思想拦截器或者过滤器的组件
// 我们发现
// Ruby Rack
// Golang matini
// Node Express, Koa 都有一个类似的中间件系统
// 对于web应用来讲，中间件在请求与相应中间开放出来可编排的扩展点，改写req res


// https://zh.wikipedia.org/wiki/%E5%9C%B0%E7%90%83%E6%A7%8B%E9%80%A0
// 物理学上，地球可划分为岩石圈、软流层、地幔、外核和内核5层。
// 化学上，地球被划分为地壳、上地幔、下地幔、外核和内核。地质学上对地球各层的划分

// 穿越地心的旅行

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
        try {
            $next();
        } catch (\Exception $ex) {
            echo "遇到", $ex->getMessage(), "\n";
        }
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
        // echo "到达<上地幔>\n";
        $next();
        echo "离开<上地幔>\n";
    };
}

function outerCore2($next)
{
    return function() use($next) {
        echo "到达<外核>\n";
        $next();
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





// 输出结构演示了这种洋葱圈结构的实现方式与执行流程

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=


// 我们把函数从内向外组合, 把内层函数的执行控制权包裹成next参数传递给外层函数
// 让外层函数自行控制内层函数执行时机, 我们再一次把控制流暴露出来
// 第一次是引入continuation, 把return决定的控制流暴露到参数中
// 于是 我们可以在外层函数 执行next时, 前后加入自己的逻辑, 得到 AOP 的before与after语义
// 但是不仅仅如此, 我们甚至可以不执行内层函数, 然后我们穿越地心的过程沿着某个半圆绕过了地核 （画图）
// 抑或，我们也可以放弃after，函数则成为 Filter，如果我们放弃before，函数则成为Terminator
// 关于错误处理, 我们可以在某层的函数 try-catch next调用, 从而阻止内层函数的异常向上传递
// 想想我们在地底深处包裹了一层可以抵御岩浆外太空物质， 于是来自深处的业火再无可能穿透上来
// 我们得到一个极简且巧妙的洋葱圈或者


// signature middleware :: (Context $ctx, $next) -> void
// 我们把多个满足中间件签名的若干个函数(callable)组合成一个函数

// 稍加改造, 我们把compose修改为生成器函数, 用来支持我们之前做出来的async函数
function compose(...$middleware)
{
    return function(Context $ctx = null, $next = null) use($middleware) {
        $ctx = $ctx ?: new Context();
        $next = $next ?: noop();

        $i = count($middleware);
        while ($i--) {
            $curr = $middleware[$i];
            $next = $curr($ctx, $next);
        }
        yield $next;
    };
}

function noop()
{
    yield;
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
        $this->context = new Context();
        $this->context->app = $this;
    }


    public function υse(callable $fn)
    {
        $this->middleware[] = $fn;
        return $this;
    }

    public function listen($port = 8000, array $config = [])
    {
        $this->fn = compose(...$this->middleware);
        $config = ['port' => $port] + $config + $this->defaultConfig();
        $this->httpServer = new \swoole_http_server($config['host'], $config['port'], SWOOLE_PROCESS, SWOOLE_SOCK_TCP);
        $this->httpServer->set($config);
        // 绑定 swoole HttpServer 事件, start shutdown connect close workerStart workerStop workerError request
        // ......
        $this->httpServer->start();
    }

    public function onRequest(\swoole_http_request $req, \swoole_http_response $res)
    {
        $ctx = $this->createContext($req, $res);
        $reqHandler = $this->makeRequestHandler($ctx);
        $resHandler = $this->makeResponseHandler($ctx);
        async($reqHandler, $resHandler);
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

    public function accept(...$types)
    {
        // TODO
        return false;
    }

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
                $this->res->$name = $value;
                return true;
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