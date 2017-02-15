<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/9
 * Time: 下午7:48
 */

function freeport()
{
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    $ok = socket_bind($socket, "0.0.0.0", 0);
    if (!$ok) {
        return false;
    }
    $ok = socket_listen($socket);
    if (!$ok) {
        return false;
    }
    $ok = socket_getsockname($socket, $addr, $port);
    if (!$ok) {
        return false;
    }
    socket_close($socket);
    return $port;
}