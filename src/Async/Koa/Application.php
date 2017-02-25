<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/26
 * Time: 上午1:35
 */

namespace Minimalism\Async\Koa;

require __DIR__ . "/../../../vendor/autoload.php";

use function Minimalism\Async\Core\co;

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
                "max_connection" => 10240,

                // 'enable_port_reuse' => true,
                'user' => 'www-data',
                'group' => 'www-data',

                // 'log_file' => __DIR__.'/swoole.log',
                'dispatch_mode' => 3,
                'open_tcp_nodelay' => 1,
                'open_cpu_affinity' => 1,
                'daemonize' => 0,
                'reactor_num' => 1,
                'worker_num' => 1,
                'max_request' => 100000,
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
        sys_echo(__FUNCTION__);
        $ctx = null;
        co(function() use($request, $response, &$ctx) {
            $response->status(404);
            $ctx = $this->createContext($request, $response);
            $fn = compose($this->middleware);
            yield $fn($ctx);
        }, function($r = null, $ex = null) use($request, $response, &$ctx) {
            sys_echo($ex);
            $response->status($ctx->status);
            $response->end($ctx->body);
        });
        return;
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


testSetCookie:
    {
        $name = "name";
        $value = "value";
        // $expire = $request->server["request_time"] + 3600;
        $expire = 0;
        $path = "/";
        $domain = "";
        $secure = false;
        $httpOnly = true;
        // string $name [, string $value = "" [, int $expire = 0 [, string $path = "" [, string $domain = "" [, bool $secure = false [, bool $httponly = false ]]]]]]
        $response->cookie($name, $value, $expire, $path, $domain, $secure, $httpOnly);
        $expect = "name=value; path=/; httponly";
        assert(in_array($expect, $response->cookie, true));
    }
*/



$app = new Application();

$app->uze(function($next) {
    /* @var $req Request */
    $req = $this->req;
    var_dump($req->header);

    /* @var $this Context */
    $start = microtime(true);
    echo 1;

    yield $next;

    echo 6, "\n";
    $end = microtime(true);

    echo $end - $start, "\n";
});

$app->uze(function($next) {
    echo 2;

    yield $next;

    echo 5;
});

$app->uze(function($next) {
    echo 3;

    yield $next;

    $this->body = "z";
    $this->status = 404;

    echo 4;
});
$app->listen();