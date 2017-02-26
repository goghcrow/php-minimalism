<?php

// linux 下 SO_SNDTIMEO 可以 设置 connect 超时
// mac os 下不可以
$s = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
$r = socket_set_option($s, SOL_SOCKET, SO_SNDTIMEO, [
    'sec' => 1,
    'usec' => 1,
]);
assert($r);

$r = socket_connect($s, "11.11.11.11", 9000);
if ($r === false) {
    echo "connect timeout";
}