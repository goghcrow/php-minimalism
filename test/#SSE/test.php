<?php

$cli = new \swoole_client(SWOOLE_TCP, SWOOLE_SOCK_ASYNC);
$cli->on("close", function(\swoole_client $cli) {
    echo "close\n";
});
$cli->on("error", function(\swoole_client $cli) {
    echo "error\n";
});
$cli->on("connect", function(\swoole_client $cli) {
    $url = str_repeat("1", 1024 * 7.9);
    $cli->send("GET /cc.php?url={$url} HTTP/1.1\r\nUser-Agent: curl\r\nHost: 112.74.59.95\r\nAccept: */*\r\n\r\n");
});
$cli->on("receive", function(\swoole_client $cli, $data) {
    var_dump($data);
    $cli->close();
});
$cli->connect("112.74.59.95", 80);


//curl http://112.74.59.95/cc.php?url=112.74.59.95


exit;

//define("MYSQL_SERVER_HOST", "10.9.34.172");
//define("MYSQL_SERVER_PORT", 3306);
//define("MYSQL_SERVER_USER", "showcase");
//define("MYSQL_SERVER_PWD", "showcase");
//define("MYSQL_SERVER_DB", "showcase");


define("MYSQL_SERVER_HOST", "10.9.51.13");
define("MYSQL_SERVER_PORT", 3008);
define("MYSQL_SERVER_USER", "crm");
define("MYSQL_SERVER_PWD", "crm");
define("MYSQL_SERVER_DB", "crm");


ini_set("memory_limit", -1);
$r = null;
$m = [];


function test()
{
    $mysql = new \swoole_mysql();

    $mysql->on("close", function() {
        echo "closed\n";
    });


    $mysql->connect([
        "host" => MYSQL_SERVER_HOST,
        "port" => MYSQL_SERVER_PORT,
        "user" => MYSQL_SERVER_USER,
        "password" => MYSQL_SERVER_PWD,
        "database" => MYSQL_SERVER_DB,
        "charset" => "utf8mb4",
    ], function(\swoole_mysql $mysql) {
        // 未连上 直接 调用query
        $mysql->query("select 1", function(\swoole_mysql $mysql, $result) {
//        global $r;
            $m[] = $result;
//
//        $r = $result;
            // xdebug_debug_zval('result');

            swoole_timer_tick(100, function() use($result) {
                global  $m;
                echo memory_get_usage(), "\n";
                // xdebug_debug_zval('result');
                $result = rand(0, 9999);
                unset($result);

                $str = '';
                for ($i = 0; $i < 9999; $i++) {
                    $str .= rand(0, 1000);
                }
                $m[] = $str;
//            echo $str, "\n";
                unset($str);
            });

//        recvResult($result);

//        swoole_timer_tick(1000, function() {
//            global $r;
//            xdebug_debug_zval('r');
//        });


            $r = $mysql->close();
//        xdebug_debug_zval('mysql');

            test();
        });
    });
}



test();

function recvResult($result) {
    xdebug_debug_zval('result');
    swoole_timer_tick(1000, function() use($result) {
        xdebug_debug_zval('result');
        unset($result);
    });
}