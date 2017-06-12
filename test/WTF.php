<?php
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
