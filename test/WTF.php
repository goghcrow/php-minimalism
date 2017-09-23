<?php


function gen()
{
    try {
        echo "a1\n";
        yield;
        echo "a2\n";
    } finally {
        echo "a3\n";
        yield;
        echo "a4\n";
    }
    echo "a5\n";
}


class A
{
    public function __construct()
    {
        $this->b = new B($this);
    }

    public function __destruct()
    {
        echo "A dtor\n";
    }
}

class B
{
    public function __construct(A $a = null)
    {
        $this->a = $a;
    }

    public function __destruct()
    {
        echo "B dtor\n";
    }
}


//// 测试循环引用
//$b = new B();
//unset($b); // 触发b 析构
//
//$a = new A();
//unset($a); // 因为循环引用, unset($a) 并不会触发析构
//
//gc_collect_cycles(); // 触发循环引用检查
//
//sleep(99999); // 脚本结束会析构, 阻止脚本结束
//


$a = new A();
$a->gen = gen();
$a->gen->current();
unset($a); // 因为循环引用, unset($a) 并不会触发析构


try {
    gc_collect_cycles(); // 触发循环引用检查
} catch (\Error $e) {
    echo $e, "\n";
}
sleep(9999);


// 生成器 & FINALLY
// 生成器析构为毛线要执行finally块，并且要恢复生成器


//function gen() {
//    try {
//        echo "1\n";
//        yield;
//        echo "2\n";
//    } finally {
//        echo "3\n";
//    }
//    echo "5\n";
//}
//
//$gen = gen();
//$gen->current();
//exit;

//
//function gen() {
//    yield;
//    try {
//        echo "1\n";
//        yield;
//        echo "2\n";
//    } finally {
//        echo "3\n";
//        yield;
//        echo "4\n";
//    }
//    echo "5\n";
//}
//
//$gen = gen();
//// $gen->current();
//$gen->next(); // 必须进入try
//
//try {
//    unset($gen); // 析构close gen触发finall中yield
//} catch (\Error $t) {
//    echo $t;
//}
//
//echo "next op\n";


//function gen() {
//    try {
//        echo "1\n";
//        yield;
//        echo "2\n";
//    } finally {
//        echo "3\n";
//        yield;
//        echo "4\n";
//    }
//    echo "5\n";
//}
//
//$gen = gen();
//$gen->current();
//unset($gen);
//exit;

/////////////////////////////////////////////////////////////////////////////////


function move_upload_file($src, $dst)
{
    $chunk = 1024 * 1024;
    $offset = 0;
    $fileSize = filesize($src);
    $n = (int)ceil($fileSize / $chunk);

    swoole_async_read($src, function($_, $content) use($src, &$offset, &$n, $dst) {
        $readSize = strlen($content);
        $continue = ($readSize !== 0);
        if ($continue) {
            var_dump($dst);
            swoole_async_write($dst, $content, $offset, function($_, $writeSize) use($offset, $readSize, &$n) {
                // assert($readSize === $writeSize); // 断言分块全部写入
                if (--$n === 0) {
                    // TODO DONE
                }
            });
        }

        $offset += $readSize;
        return $continue;
    });
}

move_upload_file(__FILE__, __FILE__ . "bak");

exit;

function x($i)
{
    $cli = new  \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
    $cli->on("close", function() use($cli, $i)  {
        echo "$i ==> close\n";
        x($i++);
    });
    $cli->on("error", function() {
        echo "error";
    });
    $cli->on("connect", function() {});
    $r = $cli->connect("10.9.193.159", 20883);
//    var_dump($r);

    swoole_timer_after(1000, function() use($cli) {
        $cli->close();
//        var_dump();
    });
}
x(1);
exit;

//$poolEx = new \swoole_connpool(\swoole_connpool::SWOOLE_CONNPOOL_REDIS);
//$r = $poolEx->setConfig([
//    "host" => "10.9.33.59",
//    "port" => 7143,
//    "password" =>  "material:br、k44emcFPBbg9fdp3WG",
//]);
//if ($r === false) {
//    throw new InvalidArgumentException("invalid connection pool config, [pool=$this->poolType]");
//}
//$poolEx->createConnPool(2, 2);
//$r = $poolEx->get(1000, function() {
//    var_dump(func_get_args());
//});
//var_dump($r);
//
//exit;

//$min = $poolConf["minimum-connection-count"];
//$max = $poolConf["maximum-connection-count"];
//if($min >= 0 && $max > 0 && $min < $max) {
//    $r = $this->poolEx->createConnPool($min, $max);
//}


$redis = new \swoole_redis();
$r = $redis->connect("10.9.33.59", 7143, function($redis, $result){
    assert($result);
    $r = $redis->auth("material:brk44emcFPBbg9fdp3WG", function($redis, $result) {
        var_dump($result);
    $redis->incr("material.video", function($redis, $result) {
        var_dump($result);
    });
    });
    assert($r);
});

exit;

$redis = new \swoole_redis([
    "password" => "material:brk44emcFPBbg9fdp3WG"
]);
$redis->connect("10.9.33.59", 7143, function($redis, $result){
    assert($result);
    $redis->incr("material.video", function($redis, $result) {
        var_dump($result);
    });
});
exit;



function get() {
    $cli = new \swoole_http_client("10.200.175.230", 80);
    $cli->on("close", function() use($cli) {
        echo "close\n";
        get();
    });
    swoole_timer_after(1, function() {});
    $cli->get("/", function($cli) {
        echo $cli->statusCode;
        $cli->close();
    });
}
get();


(new HttpServer())->listen(3000);

// /bin/bash -c "ulimit -Sc unlimited; exec ...."

class HttpServer
{
    /**
     * @var \swoole_http_server
     */
    public $httpServer;

    public function defaultConfig()
    {
        return [
            "host" => "0.0.0.0",
            "ssl" => false,
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

    public function listen($port = 8000, array $config = [])
    {
        $config = ['port' => $port] + $config + $this->defaultConfig();
        $this->httpServer = new \swoole_http_server($config['host'], $config['port'], SWOOLE_PROCESS, SWOOLE_SOCK_TCP);
        $this->httpServer->set($config);
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

    private $timerMap = [];
    public function onRequest(\swoole_http_request $req, \swoole_http_response $res)
    {
        $fw1 = "10.9.189.90";
        $php7_test = "10.9.83.107";
        $cli = new \swoole_http_client($php7_test, 80);

        $timerId = swoole_timer_after(1000, static function() use($cli, $res, &$timerId) {
            $res->status(500);

            $cost = microtime(true) - $this->timerMap[$timerId];
            sys_echo("client timeout to close timerId = $timerId cost = $cost");
            $cli->close();
            unset($this->timerMap[$timerId]);
        });
        $this->timerMap[$timerId] = microtime(true);

        $cli->on("close", static function() use($timerId) {
            if (swoole_timer_exists($timerId)) {
                swoole_timer_clear($timerId);
                unset($this->timerMap[$timerId]);
            }
            // sys_echo("client closed");
        });

        $cli->get("/test", static function($resp) use($timerId, $res, $cli) {
            if (swoole_timer_exists($timerId)) {
                swoole_timer_clear($timerId);
                unset($this->timerMap[$timerId]);
            }
            $res->status(200);
            $res->end($resp->body);
            $cli->close();
        });
    }
}

function sys_echo($context) {
    // $_SERVER 会被swoole setglobal 清空, 这里用 $_ENV
    $workerId = isset($_ENV["WORKER_ID"]) ? $_ENV["WORKER_ID"] : "";
    $time = date("Y-m-d H:i:s", time());
    echo "[$time #$workerId] $context\n";
}

function sys_error($context) {
    $workerId = isset($_ENV["WORKER_ID"]) ? $_ENV["WORKER_ID"] : "";
    $time = date("Y-m-d H:i:s", time());
    fprintf(STDERR, "[$time #$workerId] $context\n");
}


exit;


$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
socket_set_option($socket, SOL_SOCKET, TCP_NODELAY, 1);
pcntl_signal(SIGPIPE, function() { });


// $ sysctl -A | grep range
// net.ipv4.ip_local_port_range = 9000	65535

if ($argc < 2) {
    exit("Usage: " . __FILE__ . " port\n");
}

self_connect($argv[1]);

function self_connect($port)
{
    for ($i = 0; $i < 65536; $i++) {
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $r = @socket_connect($socket, "127.0.0.1", $port);
        if ($r === false) {
            $errno = socket_last_error($socket);
            if ($errno === SOCKET_ECONNREFUSED) {
                continue;
            } else {
                echo socket_strerror($errno), "\n";
                break;
            }
        } else {
            socket_getsockname($socket, $localAddr, $localPort);
            socket_getpeername($socket, $peerAddr, $peerPort);
            echo "Connected ($localAddr : $localPort)  ($peerAddr : $peerPort)\n";
        }
    }
}


exit;
$tcp_pool = new \swoole_connpool(\swoole_connpool::SWOOLE_CONNPOOL_TCP);
$r = $tcp_pool->setConfig([
    "host" => "10.9.37.103",
    "port" => 2280,
]);
$tcp_pool->createConnPool(10, 10);
$tcp_pool->get(100, function($self, $client) {
    assert($client !== false);
    var_dump($client->isConnected());
    $r = $client->sendwithcallback("HELLO", function() {
        var_dump(func_get_args());
    });


//    var_dump($r);
    // swoole_event_exit();
});

exit;



date_default_timezone_set('Asia/Shanghai');
swoole_timer_after(5000, function() {
    echo "evtime:" , date("H:i:s", nova_get_time()), "\n";
    echo "systime:", date("H:i:s", time()). "\n";
});

exit;

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=


function int2Bytes($int)
{
    return pack("L", $int);
}

function int2BytesArray($int)
{
    return unpack("C*", pack("L", $int));
}

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=


/*
opcache.enable=1
opcache.enable_cli=1
opcache.optimization_level=0xffffffff
*/

function foreach_infinite_loop()
{
    $arr = [1,2];
    $j = 0;
    $cond = true;
    foreach ($arr as $i => $v){
        while(1){
            if($cond){
                break;
            }
        }
        $j++;
        echo $j . "\n";
        if($j>10) break;
    }
}
foreach_infinite_loop();
exit;


// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

// client
$cli = new \swoole_http_client("127.0.0.1", 8888);
$cli->get("/", function($cli) {
    var_dump($cli);
    $cli->close();
});
exit;

// server
function send(\swoole_server $serv, $fd, $msg) {
    swoole_timer_after(100, function() use($serv, $fd, $msg) {
        $toSend = substr($msg, 0, 1);
        echo $toSend;
        $serv->send($fd, $toSend);
        $msg = substr($msg, 1);
        if (strlen($msg) > 0) {
            send($serv, $fd, $msg);
        }
    });
}


$serv = new \swoole_server("0.0.0.0", 8888);
$serv->set(["worker_num" => 1]);
$serv->on("receive", function(\swoole_server $serv, $fd) {
    $msg = "HTTP/1.1 200 OK\r\nContent-Length: 5\r\nA: B\r\n\r\nHELLO";
    send($serv, $fd, $msg);

//    $serv->send($fd, "HTTP/1.1 200 OK\r\n");
//    $serv->send($fd, "Content-Length: 5\r\n");

//    $serv->send($fd, "A");
//    swoole_timer_after(100, function() use($serv, $fd) {
//        $serv->send($fd, ": B\r\n\r\nHELLO");
//    });

//    $serv->send($fd, "HE");
//    swoole_timer_after(100, function() use($serv, $fd) {
//        $serv->send($fd, "LLO: WORLD\r\n\r\nHELLO");
//    });
});
$serv->start();
exit;
// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=


$conf = [
    "host" => "127.0.0.1",
    "port" => 3306,
    "user" => "root",
    "password" => "123456",
    "database" => "test",
    "charset" => "utf8mb4",
];

$mysql = new \swoole_mysql();
$mysql->connect($conf, function(\swoole_mysql $mysql, $result) {



    $mysql->begin(function(\swoole_mysql $mysql) {
        // 加入定时器会把代码执行权转移到vm dispatch loop
        // swoole_timer_after(1, function() {});

        // 或者 包裹 try-catch 会将异常立即抛出

        // 异常没有被立即抛出
        $mysql->begin(function() {});
        echo 1;
    });



});
// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=


class A
{
    private $flag;

    public function __construct()
    {
        $this->flag = false;

        $this->init();
    }

    public function init()
    {
        // debug here
        if ($this->flag) {
            return;
        }

        $this->flag = true;
        echo "init\n";
    }

    public function __debugInfo()
    {
        $this->flag = true;
        return [
            "flag" => false,
        ];
    }
}

new A();
exit;


// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

function λ()
{

}

function β()
{

}

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

class Proto
{
    public $prop;

    public function __construct($prop)
    {
        $this->prop = $prop;
    }

    public function getClosure()
    {
        return function() {
            return $this->prop;
        };
    }
}

$prop = new Proto("hello");
$closure = $prop->getClosure();
echo $closure(), "\n";
$prop->prop = "world";
echo $closure(), "\n";

$clone = clone $prop;
$clone->prop = "abc";
$closure = $closure->bindTo($clone, Proto::class);
echo $closure(), "\n";


// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

$obj = new \__PHP_Incomplete_Class();
assert(is_object($obj) === false);

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

class Call
{
    public static function __callStatic($name, $arguments)
    {
        var_dump($name);
    }
}

call_user_func([Call::class, "method\0trunked"]);

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

class _
{

}

class B extends _ {
    public $public = 1;
    protected $protected = 2;
    private $private = 3;
}

$c = function() {
    $this->public;
    $this->protected; // 可以访问 protected
    // $this->private;
};

$c = $c->bindTo(new B, _::class);
$c();


// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

// ini_set("xdebug.max_nesting_level", PHP_INT_MAX);
// function r(){$self = __FUNCTION__;$self();}r();

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
