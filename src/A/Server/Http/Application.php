<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/26
 * Time: 上午1:35
 */

namespace Minimalism\A\Server\Http;


use function Minimalism\A\Core\async;

class Application
{
    /**
     * @var \swoole_http_server
     */
    public $httpServer;
    public $context;
    public $middleware = [];

    public function __construct()
    {
        $this->context = new Context();
        $this->context->app = $this;
    }

    public function listen(array $config = [])
    {
        $config = $config + [
                "host" => "0.0.0.0",
                "port" => 8000,
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

        $flag = $config["ssl"] ? SWOOLE_SOCK_TCP | SWOOLE_SSL : SWOOLE_SOCK_TCP;
        $this->httpServer = new \swoole_http_server($config["host"], $config["port"], SWOOLE_PROCESS, $flag);
        $this->httpServer->set($config);
        /*
        enum http_global_flag
        {
            HTTP_GLOBAL_GET       = 1u << 1,
            HTTP_GLOBAL_POST      = 1u << 2,
            HTTP_GLOBAL_COOKIE    = 1u << 3,

            HTTP_GLOBAL_REQUEST   = 1u << 4,
            HTTP_GLOBAL_SERVER    = 1u << 5,
            HTTP_GLOBAL_FILES     = 1u << 6,
        };
            REGISTER_LONG_CONSTANT("HTTP_GLOBAL_GET", HTTP_GLOBAL_GET, CONST_CS | CONST_PERSISTENT);
            REGISTER_LONG_CONSTANT("HTTP_GLOBAL_POST", HTTP_GLOBAL_POST, CONST_CS | CONST_PERSISTENT);
            REGISTER_LONG_CONSTANT("HTTP_GLOBAL_COOKIE", HTTP_GLOBAL_COOKIE, CONST_CS | CONST_PERSISTENT);
            REGISTER_LONG_CONSTANT("HTTP_GLOBAL_ALL", HTTP_GLOBAL_GET| HTTP_GLOBAL_POST| HTTP_GLOBAL_COOKIE | HTTP_GLOBAL_REQUEST |HTTP_GLOBAL_SERVER | HTTP_GLOBAL_FILES, CONST_CS | CONST_PERSISTENT);
        */
        $this->httpServer->setglobal(HTTP_GLOBAL_ALL, HTTP_GLOBAL_GET | HTTP_GLOBAL_POST | HTTP_GLOBAL_COOKIE);


        $this->httpServer->on('start', [$this, 'onStart']);
        $this->httpServer->on('shutdown', [$this, 'onShutdown']);

        $this->httpServer->on('workerStart', [$this, 'onWorkerStart']);
        $this->httpServer->on('workerStop', [$this, 'onWorkerStop']);
        $this->httpServer->on('workerError', [$this, 'onWorkerError']);

        $this->httpServer->on('connect', [$this, 'onConnect']);
        $this->httpServer->on('request', [$this, 'onRequest']);
        $this->httpServer->on('close', [$this, 'onClose']);

        $sock = $this->httpServer->getSocket();
        if (!socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1)) {
            sys_error("Unable to set option on socket: " . socket_strerror(socket_last_error()));
        }

        $this->httpServer->start();
    }

    public function onConnect()
    {
        sys_echo(__FUNCTION__);
    }

    public function onClose()
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

    public function onRequest(\swoole_http_request $request, \swoole_http_response $response)
    {
        $req = new Request($request);
        $res = new Response($response);
        async($this->handleRequest($req, $res), [$this, "onRequestFinish"]);
    }

    public function uze(\Closure $fn)
    {
        $this->middleware[] = $fn;
        return $this;
    }

    public function createContext($req, $res)
    {
        $context = clone $this->context;

        $context->req = $req;
        $context->res = $res;

        return $context;
    }

    protected function handleRequest(Request $request, Response $response)
    {
        return function() use($request, $response) {
            $response->status(404);
            $fn = compose($this->middleware);
            $ctx = $this->createContext($request, $response);
            yield $fn($ctx);
            yield $ctx;
        };
    }

    public function onRequestFinish(Context $ctx, \Exception $ex = null)
    {
        if ($ex) {
            sys_echo($ex);
            if ($ex->getCode() === 404) {
                return;
            }
        } else {
            $this->respond($ctx);
        }
    }

    protected function respond(Context $ctx)
    {
        $res = $ctx->res;
        $body = $ctx->body;
        $code = $ctx->code;

        $res->status($code);
        $res->end($body);
    }
}

/*
        $response->status(404);
        $response->end();
//        $this->httpServer->send($request->fd, "HTTP/1.1 200 OK\r\nContent-Length: 4\r\n\r\npong\r\n");
//        $level = 9;
//        $response->gzip($level);
//        $response->end();
$uri = $request->server["request_uri"];

$request->server['request_method'];
$request->server['request_uri'];
$response->status(404);
$request->get;
$request->post;
$request->cookie;
$request->header;

$response->sendfile(__FILE__);

$response->header("Content-Type", "application/json");
$response->end(json_encode($request->server, JSON_PRETTY_PRINT));

// $response->rawcookie();
// $request->rawContent();

        // string $name [, string $value = "" [, int $expire = 0 [, string $path = "" [, string $domain = "" [, bool $secure = false [, bool $httponly = false ]]]]]]
        $response->cookie($name, $value, $expire, $path, $domain, $secure, $httpOnly);
*/