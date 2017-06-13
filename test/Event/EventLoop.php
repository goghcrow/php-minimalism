<?php

namespace Minimalism\Test\Event;


use Minimalism\Event\EventLoop;
use Minimalism\Event\TcpClient;

require __DIR__ . "/../../src/Event/EventLoop.php";
require __DIR__ . "/../../src/Event/TcpClient.php";


function testNestedNextTick()
{
    $ev = new EventLoop();
    $ev->tick(1000, function() {
        echo "\nHELLO\n";
    });
    $y = function(EventLoop $ev) use(&$y) {
        $ev->nextTick(function(EventLoop $ev) use($y) {
            echo ".";
            $y($ev);
        });
    };
    $y($ev);
}

// testNestedNextTick();


function testTimer()
{
    $ev = new EventLoop();

    $ev->after(1000, function(EventLoop $ev) {
        echo "start\n";
        $ev->tick(1000, function(EventLoop $ev) {
            echo "*";
            $ev->nextTick(function() {
                echo "!\n";
            });
        });
    });
}

// testTimer();

function testClearTimer()
{
    $ev = new EventLoop();
    $tickId = $ev->tick(500, function() {
       echo "*";
    });
    $ev->after(5000, function(EventLoop $ev) use($tickId) {
        $ev->clear($tickId);
    });
}

// testClearTimer();


function testOnRead()
{
    $ev = new EventLoop();
    stream_set_blocking(STDIN, 0);
    $ev->onRead(STDIN, function(EventLoop $ev, $s) {
        echo fread($s, 1024), "\n";
    });
}

//testOnRead();

$ev = new EventLoop();

$tcpClient = new TcpClient($ev);
$tcpClient->on("connect", function(TcpClient $cli, $s) {
    echo "ON_CONNECT\n\n";
    $r = $cli->send("GET / HTTP/1.1\r\n\r\n");
    var_dump($r);
});
$tcpClient->on("receive", function(TcpClient $cli, $s, $recv) {
    echo "ON_RECEIVE\n\n";
    var_dump($recv);

    $cli->ev->after(1000, function() use($cli) {
        $cli->send("GET / HTTP/1.1\r\n\r\n");
    });
});
$tcpClient->on("close", function(TcpClient $cli, $s) {
    echo "ON_CLOSED\n";
    // var_dump($cli->errno);
    // var_dump($cli->errstr);
});

$tcpClient->connect("tcp://115.239.211.112:80");



function testTcpClient()
{
    $ev = new EventLoop();
    $ev->tick(1000, function() {
        echo ".\n";
    });

    for ($i = 0; $i < 10; $i++) {
        $s = stream_socket_client("tcp://180.150.190.136:80", $errno, $errstr, 0, STREAM_CLIENT_ASYNC_CONNECT);
        stream_set_blocking($s, 0);


        $ev->onRead($s, function(EventLoop $ev, $s) {
            $recv = fread($s, 1024);
            if ($recv === "" || $recv === false) {
                echo "close\n";
                fclose($s);
                $ev->onRead($s, null);
            } else {
                echo "onRead\n";
                echo substr($recv, 0, 10), "\n";
            }
        });

        $ev->onWrite($s, function(EventLoop $ev, $s) {
            echo "onWrite\n";
            $l = fwrite($s, "GET / HTTP 1.1\r\n\r\n");
            echo $l, "\n\n";

            $ev->onWrite($s, null);
        });
    }
}


