<?php

//$hex = "51000001088208002c010000210000000000000000000000000000000000000000000000726f6f740014e9d14ce19bfa8d3a983a5dbe17400e0b25ed130374657374006c5f6e61746976655f70617373776f726400";

//$r = unpack("v", hex2bin(51000001));
//var_dump($r);
//exit;

$mysql = new \swoole_mysql();

$server = [
    "host" => "127.0.0.1",
    "port" => 7777,
    "user" => "root",
    "password" => "123456",
    "database" => "test"
];

$mysql->on("close", function() { echo "close\n"; });

$mysql->connect($server, function ($db, $r) {
    var_dump($db);
    var_dump($r);
});