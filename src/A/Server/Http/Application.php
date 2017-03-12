<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/26
 * Time: 上午1:35
 */

namespace Minimalism\A\Server\Http;


use function Minimalism\A\Core\async;
use function Minimalism\A\Core\setCtx;
use Minimalism\A\Server\Http\Exception\HttpException;

/**
 * Class Application
 * @package Minimalism\A\Server\Http
 *
 * @see http://koajs.com/
 * A Koa application is an object containing an array of middleware functions
 * which are composed and executed in a stack-like manner upon request.
 */
class Application
{
    /**
     * @var \swoole_http_server
     */
    public $httpServer;

    /**
     * Prototype Context
     * @var Context
     */
    public $context;

    public $middleware = [];

    public $fn;

    public $silent = true;

    public function __construct()
    {
        $this->context = new Context();
        $this->context->app = $this;
    }

    public function defaultConfig()
    {
        return [
            // 'log_file' => __DIR__.'/swoole.log',
            // "user" => "www-data",
            // "group" => "www-data",
            "host" => "0.0.0.0",
            "max_connection" => 10240,
            'max_request' => 100000,
            'dispatch_mode' => 3,
            "open_tcp_nodelay" => 1,
            "open_cpu_affinity" => 1,
            "daemonize" => 0,
            "reactor_num" => 1,
            "worker_num" => \swoole_cpu_num(),
        ];
    }

    /**
     * @param \Closure|callable $fn
     *      public function __invoke(Context $ctx, $next);
     * @return $this
     *
     * Keywords can be used as name since php7
     * υ Greek alphabet
     */
    public function υse(callable $fn)
    {
        $this->middleware[] = $fn;
        return $this;
    }

    public function listen($port = 8000, array $config = [])
    {
        $this->fn = compose(...$this->middleware);

        $config = ['port' => $port] + $config + $this->defaultConfig();
        $flag = $config['ssl'] ? SWOOLE_SOCK_TCP | SWOOLE_SSL : SWOOLE_SOCK_TCP;

        $this->httpServer = new \swoole_http_server($config['host'], $config['port'], SWOOLE_PROCESS, $flag);
        $this->httpServer->set($config);
        // $this->httpServer->setglobal(HTTP_GLOBAL_ALL, HTTP_GLOBAL_GET | HTTP_GLOBAL_POST | HTTP_GLOBAL_COOKIE);

        $this->bindEvent();
        $this->httpServer->start();
    }

    protected function bindEvent()
    {
        $this->httpServer->on('start', [$this, 'onStart']);
        $this->httpServer->on('shutdown', [$this, 'onShutdown']);
        $this->httpServer->on('connect', [$this, 'onConnect']);
        $this->httpServer->on('close', [$this, 'onClose']);

        $this->httpServer->on('workerStart', [$this, 'onWorkerStart']);
        $this->httpServer->on('workerStop', [$this, 'onWorkerStop']);
        $this->httpServer->on('workerError', [$this, 'onWorkerError']);
        $this->httpServer->on('request', [$this, 'onRequest']);

        // output buffer overflow, reactor will block, dont wait
        \swoole_async_set(["socket_dontwait" => 1]);
    }

    public function onConnect(\swoole_http_server $httpServer)
    {
        sys_echo(__FUNCTION__);
    }

    public function onClose(\swoole_http_server $httpServer)
    {
        sys_echo(__FUNCTION__);
    }

    public function onStart(\swoole_http_server $httpServer)
    {
        sys_echo(__FUNCTION__);
    }

    public function onShutdown(\swoole_http_server $httpServer)
    {
        sys_echo(__FUNCTION__);
    }

    public function onWorkerStart(\swoole_http_server $httpServer, $workerId)
    {
        $_ENV["WORKER_ID"] = $workerId;
        sys_echo("worker #$workerId start");
    }

    public function onWorkerStop(\swoole_http_server $httpServer, $workerId)
    {
        sys_echo("worker #$workerId stop");
    }

    public function onWorkerError(\swoole_http_server $httpServer, $workerId, $workerPid, $exitCode, $sigNo)
    {
        sys_error("worker error happen [workerId=$workerId, workerPid=$workerPid, exitCode=$exitCode, signalNo=$sigNo]");
    }

    public function onRequest(\swoole_http_request $req, \swoole_http_response $res)
    {
        $ctx = $this->createContext($req, $res);
        $reqHandler = $this->makeRequestHandler($ctx);
        $resHandler = $this->makeResponseHandler($ctx);
        // TODO: onFinish : defer
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

        if ($ex && $ex->getCode() !== 404 && !$this->silent) {
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