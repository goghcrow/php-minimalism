<?php

namespace Minimalism\Test\Coroutine;

use function Minimalism\Coroutine\await;
use function Minimalism\Coroutine\callcc;
use function Minimalism\Coroutine\chan;
use function Minimalism\Coroutine\fork;
use function Minimalism\Coroutine\getTask;
use function Minimalism\Coroutine\go;
use function Minimalism\Coroutine\select;
use Minimalism\Coroutine\Time;
use Minimalism\Validation\P;

require __DIR__ . "/../../vendor/autoload.php";

function print_stack()
{
    $task = (yield getTask());
    $stack = $task->getStack();
    foreach ($stack as $item) {
        echo $item, "\n";
    }
}

function a()
{
    yield b();
}

function b()
{
    yield c();
}


function c()
{
    yield gethostbyname("www.youzan.com");
    yield print_stack();

}

function gethostbyname($host, $timeout = 100)
{
    return callcc(function($k) use($host, $timeout) {
        $timer = swoole_timer_after($timeout, function() use($k) {
            $k(null, new \Exception("gethostbyname timeout"));
        });
        swoole_async_dns_lookup($host, function($_, $ip) use($k, $timer) {
            if (swoole_timer_exists($timer)) {
                swoole_timer_clear($timer);
                $k($ip);
            }
        });
    });
}


go(function() {
    yield await(function() {
        yield await(function() {
            yield a();
        });
    });
});
