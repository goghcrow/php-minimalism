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
use Minimalism\Event\EventEmitter;

/**
 * Class Application
 * @package Minimalism\A\Server\Http
 *
 * @see http://koajs.com/
 * A Koa application is an object containing an array of middleware functions
 * which are composed and executed in a stack-like manner upon request.
 *
 * TODO 斟酌是否继承 EventEmitter
 * TODO 梳理错误处理
 * TODO onFinish
 * TODO Timeout Middleware
 */
class Application extends EventEmitter
{
    /**
     * @var \swoole_http_server
     */
    public $httpServer;

    public $context;

    public $middleware = [];

    public $fn;

    public function __construct()
    {
        $this->context = new Context();
        $this->context->app = $this;
    }

    public function defaultConfig()
    {
        return [
            "host" => "0.0.0.0",
            "ssl" => false,
            // 'ssl_cert_file' => 'path/to/server.crt',
            // 'ssl_key_file' => 'path/to/server.key',

            // 'enable_port_reuse' => true,

            // 'log_file' => __DIR__.'/swoole.log',
            // "user" => "www-data",
            // "group" => "www-data",

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
        $this->fn = compose($this->middleware);

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
        socket_set_option($this->httpServer->getSocket(), SOL_SOCKET, SO_REUSEADDR, 1);
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
                if ($onError = $ctx->onError) {
                    $onError($ctx, $ex);
                }
            } else {
                $this->respond($ctx);
            }
        };
    }

    protected function respond(Context $ctx)
    {
        if ($ctx->respond === false) return; // for hacker

        if ($ctx->status !== null) {
            $ctx->res->status($ctx->status);
        }

        if ($ctx->body !== null) {
            $ctx->res->write($ctx->body);
        }

        $ctx->res->end();
    }

    /**
     * default exception Handler
     *  可自行添加 ExceptionHandler middleware
     * @param Context $ctx
     * @param \Exception $ex
     */
    public function handleError(Context $ctx, \Exception $ex)
    {
        sys_error($ex);
    }

    protected function createContext(\swoole_http_request $req, \swoole_http_response $res)
    {
        // 可以在Context挂其他组件 $app->com = ; $app->listen();
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