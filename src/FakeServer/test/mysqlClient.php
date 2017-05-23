<?php

//$hex = "51000001088208002c010000210000000000000000000000000000000000000000000000726f6f740014e9d14ce19bfa8d3a983a5dbe17400e0b25ed130374657374006c5f6e61746976655f70617373776f726400";

//$r = unpack("v", hex2bin(51000001));
//var_dump($r);
//exit;

// 连接池分支 server 主动关闭会触发coredump！！！


define("MYSQL_SERVER_HOST", "10.9.34.172");
define("MYSQL_SERVER_PORT", 3306);
define("MYSQL_SERVER_USER", "showcase");
define("MYSQL_SERVER_PWD", "showcase");
define("MYSQL_SERVER_DB", "showcase");


function cb(\swoole_mysql $mysql, $r)
{
    var_dump($r);
}

function connect_to_fake_serv()
{
    $mysql = new \swoole_mysql();

    $server = [
        "host" => "127.0.0.1",
        "port" => 7777,
        "user" => "root",
        "password" => "123456",
        "database" => "test"
    ];

    $server = [
        "host" => MYSQL_SERVER_HOST,
        "port" => MYSQL_SERVER_PORT,
        "user" => MYSQL_SERVER_USER,
        "password" => MYSQL_SERVER_PWD,
        "database" => MYSQL_SERVER_DB,
        "charset" => "utf8mb4",
    ];

    $mysql->on("close", function() {
        echo "close\n\n";
        connect_to_fake_serv();
    });

    $mysql->connect($server, function (\swoole_mysql $mysql, $r) {
        // echo $mysql->connect_errno, "\n";
        assert($r === true);
        echo "connected\n";
        // var_dump($db);
        // var_dump($r);

       $mysql->query("select 1", function(\swoole_mysql $mysql, $r) {
          var_dump($r);
       });
//        $mysql->query("select 1", "cb");
    });
}

connect_to_fake_serv();