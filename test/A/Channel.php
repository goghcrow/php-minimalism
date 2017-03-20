<?php

namespace Minimalism\Test\A;


use function Minimalism\A\Client\async_curl_get;
use function Minimalism\A\Client\async_sleep;
use Minimalism\A\Core\BufferChannel;
use function Minimalism\A\Core\chan;
use Minimalism\A\Core\Channel;
use function Minimalism\A\Core\go;

require __DIR__ . "/../../vendor/autoload.php";


// 人工执行 Channel
function t0()
{
    $ch = new Channel();

    $recvAsync = $ch->recv();
    $recvAsync->begin(function($r, $ex) {
        // echo "recv return ";
        // var_dump($r); // 42
        assert($r === 42);
    });

    $sendAsync = $ch->send(42);
    $sendAsync->begin(function($r, $ex) {
        // echo "send return ";
        // var_dump($r); // null
        assert($r === null);
    });
}

//t0();

function t1()
{
    $ch = new Channel();

    go(function() use($ch) {
        echo "before recv\n";
        $recv = (yield $ch->recv());
        var_dump($recv);
    });

    go(function() use($ch) {
        echo "before send\n";
        $res = (yield async_curl_get("www.baidu.com"));
        yield $ch->send($res->statusCode);
        echo "after send\n";
    });
}

//t1();


function t2()
{
    $ch = new Channel();

    go(function() use($ch) {
        while (true) {
            yield $ch->send("producer 1");
            yield async_sleep(1000);
        }
    });

    go(function() use($ch) {
        while (true) {
            yield $ch->send("producer 2");
            yield async_sleep(1000);
        }
    });

    go(function() use($ch) {
        while (true) {
            $recv = (yield $ch->recv());
            echo "consumer1 recv from $recv\n";
        }
    });

    go(function() use($ch) {
        while (true) {
            $recv = (yield $ch->recv());
            echo "consumer2 recv from $recv\n";
        }
    });
}

// t2();


// timeout
function t3()
{
    $ch = new Channel();

    go(function() use($ch) {
        $ex = null;
        try {
            recv:
            yield $ch->recv(1000);
        } catch (\Exception $ex) {
            echo "ex\n";
            goto recv;
        }
    });


    go(function() use($ch) {
        yield async_sleep(2000);
        echo "send\n";
        yield $ch->send(42);
    });
}
// t3();


function t4()
{
    $ch = new BufferChannel(3);

    go(function() use($ch) {
        $recv = (yield $ch->recv());
        echo "recv $recv\n";
        $recv = (yield $ch->recv());
        echo "recv $recv\n";
        $recv = (yield $ch->recv());
        echo "recv $recv\n";
        $recv = (yield $ch->recv());
        echo "recv $recv\n";
    });

    go(function() use($ch) {
        yield $ch->send(1);
        echo "send 1\n";
        yield $ch->send(2);
        echo "send 2\n";
        yield $ch->send(3);
        echo "send 3\n";
        yield $ch->send(4);
        echo "send 4\n";
    });
}

//t4();


function t5()
{
    $ch = chan(rand(1, 4));

    go(function() use($ch) {
        $recv = (yield $ch->recv());
        echo "recv $recv\n";
        $recv = (yield $ch->recv());
        echo "recv $recv\n";
        $recv = (yield $ch->recv());
        echo "recv $recv\n";
        $recv = (yield $ch->recv());
        echo "recv $recv\n";
    });

    go(function() use($ch) {
        yield $ch->send(1);
        echo "send 1\n";
        yield $ch->send(2);
        echo "send 2\n";
        yield $ch->send(3);
        echo "send 3\n";
        yield $ch->send(4);
        echo "send 4\n";
    });
}

// t5();


function t51()
{
    $ch = new BufferChannel(1);

    go(function() use($ch) {
        yield $ch->send(1);
        echo "send 1\n";
        yield $ch->send(2);
        echo "send 2\n";

        yield async_sleep(1000);
        echo "send: after 1s\n";
    });


    go(function() use($ch) {
        $recv = (yield $ch->recv());
        echo "recv $recv\n";

        // sleep 不会阻塞第二次send
        yield async_sleep(1000);
        echo "recv: after 1s\n";

        $recv = (yield $ch->recv());
        echo "recv $recv\n";
    });
}
//t51();

function t6()
{
    $ch = chan(2);

    go(function() use($ch) {
        while (true) {
            $recv = (yield $ch->recv());
            echo "a recv\n";
            yield async_sleep(1);
        }
    });

    go(function() use($ch) {
        while (true) {
            $recv = (yield $ch->recv());
            echo "b recv\n";
            yield async_sleep(1);
        }
    });

    go(function() use($ch) {
        while (true) {
            yield $ch->send(null);
            echo "c send\n";
            yield async_sleep(1);
        }
    });

    go(function() use($ch) {
        while (true) {
            yield $ch->send(null);
            echo "d send\n";
            yield async_sleep(1);
        }
    });
}

//t6();

function t7()
{
    $ch = chan();

    go(function() use ($ch) {
        $anotherCh = chan();
        yield $ch->send($anotherCh);
        echo "send another channel\n";
        yield $anotherCh->send("HELLO");
        echo "send hello through another channel\n";
    });

    go(function() use($ch) {
        /** @var Channel $anotherCh */
        $anotherCh = (yield $ch->recv());
        echo "recv another channel\n";
        $val = (yield $anotherCh->recv());
        echo $val, "\n";
    });
}

// t7();

function t8()
{
    $ch = chan(2);

    go(function() use($ch) {
        while (true) {
            list($host, $status) = (yield $ch->recv());
            echo "$host: $status\n";
        }
    });

    go(function() use($ch) {
        while (true) {
            $host = "www.baidu.com";
            $resp = (yield async_curl_get($host));
            yield $ch->send([$host, $resp->statusCode]);
        }
    });

    go(function() use($ch) {
        while (true) {
            $host = "www.bing.com";
            $resp = (yield async_curl_get($host));
            yield $ch->send([$host, $resp->statusCode]);
        }
    });
}

// t8();


function pingPong()
{
    $pingCh = chan();
    $pongCh = chan();
    
    go(function() use($pingCh, $pongCh) {
        while (true) {
            echo (yield $pingCh->recv());
            yield $pongCh->send("PONG\n");

            yield async_sleep(1);
        }
    });
    
    go(function() use($pingCh, $pongCh) {
        while (true) {
            echo (yield $pongCh->recv());
            yield $pingCh->send("PING\n");

            yield async_sleep(1);
        }
    });
    
    go(function() use($pingCh) {
        echo "start up\n";
        yield $pingCh->send("PING\n");
    });
}
pingPong();