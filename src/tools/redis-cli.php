#!/usr/bin/env php
<?php

/**
 * 简易 redis-cli
 */

$opt = getopt('h:p:s:c:');

if (isset($opt["c"]) && isset($opt["h"]) && isset($opt["p"])) {
    $host = $opt["h"];
    $port = $opt["p"];
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if (socket_connect($socket, $host, $port) === false) {
        exit("connect $host:$port fail\n");
    }

} else if (isset($opt["c"]) && isset($opt["s"])) {
    $path = $opt["s"];
    $socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
    if (socket_connect($socket, $path) === false) {
        exit("connect $path fail\n");
    }
} else {
    $usage = "Usage: $argv[0] -h 10.9.12.205 -p 6666 -c cmd\n" .
             "       $argv[0] -s /var/run/yz-tether/redis2aerospike.sock -c cmd\n";
    exit($usage);
}


$cmds = preg_split("/[\s]+/", $opt["c"]);
$raw = "*" . count($cmds) . "\r\n";
foreach ($cmds as $cmd) {
    $raw .= "$" . strlen($cmd). "\r\n$cmd\r\n";
}
//echo "$raw\r\n";
socket_write($socket, $raw);

echo socket_read($socket, 8192);
socket_close($socket);