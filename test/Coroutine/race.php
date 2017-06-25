<?php

namespace Minimalism\Test\Coroutine;

use Minimalism\Coroutine\AsyncTimeoutException;
use function Minimalism\Coroutine\callcc;
use function Minimalism\Coroutine\go;
use function Minimalism\Coroutine\once;
use function Minimalism\Coroutine\race;
use Minimalism\Coroutine\Time;

require __DIR__ . "/../../vendor/autoload.php";



function with_time(callable $fn, $timeout)
{
    return function($k) use($fn, $timeout) {
        $k = once($k);
        $fn($k);
        swoole_timer_after($timeout, function() use($k) {
            $k(null, new AsyncTimeoutException());
        });
    };
}


// 一个race处理超时的示例
go(function() {

    function delay($ms) {
        return callcc(function($k) use($ms) {
            swoole_timer_after($ms, function() use($k) {
                $k(true);
            });
        });
    }

    function timeout_ex($ms)
    {
        yield Time::sleep($ms);
        throw new \Exception("timeout");
    }


    // -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

    $timeout = Time::sleep(100);
    $dnslookup = delay(10);

    $r = (yield race([
        $timeout,
        $dnslookup,
    ]));
    assert($r === true);

    // -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

    $timeout = Time::sleep(1);
    $dnslookup = delay(100);

    $r = (yield race([
        $timeout,
        $dnslookup,
    ]));
    assert($r === null); // 超时直接返回 null




    // -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

    $timeout = Time::sleep(100);
    $dnslookup = timeout_ex(10);

    $ex = null;
    try {
        $r = (yield race([
            $timeout,
            $dnslookup,
        ]));
    } catch (\Exception $ex) {
    }
    assert($ex && $ex->getMessage() === "timeout");
});

