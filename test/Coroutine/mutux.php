<?php

namespace Minimalism\Test\Coroutine;

use function Minimalism\Coroutine\dd;
use Minimalism\Coroutine\Sync\Mutex;
use function Minimalism\Coroutine\callcc;
use function Minimalism\Coroutine\go;
use Minimalism\Coroutine\Time;
use function Minimalism\Coroutine\wg;

require __DIR__ . "/../../vendor/autoload.php";



// swoole_async_dns_lookup 这个函数回调可能同步也可能异步，有问题
function f() {
    swoole_async_dns_lookup("www.youzan.com", function() {
        echo "1\n";
        // swoole_event_defer(function() {
            f();
        // });
    });
}
// f();exit;


function gethostbyname($host)
{
    return callcc(function($k) use($host) {
        swoole_async_dns_lookup($host, function($_, $ip) use($k) {
            $k($ip);
        });
    });
}


go(function() {
    $g_wg = wg(2);

    go(function() use($g_wg) {
        $mutex = new Mutex();
        $wg = wg(10);
        $count = 0;

        $criticalZone = function() use($mutex, $wg, &$count) {
            static $init = [];

            yield $mutex->lock();

            try {
                if (!isset($init["ip"])) {
                    $count++;
                    $init["ip"] = (yield gethostbyname("www.google.com"));
                }
            } finally {
                yield $mutex->unlock();
            }

            $wg->done();
        };


        for ($i = 0; $i < 10; $i++) {
            go($criticalZone);
        }

        yield $wg->wait();

        assert($count === 1);

        $g_wg->done();
    });




    go(function() use($g_wg) {
        $mutex = new Mutex();
        $wg = wg(10);
        $count = 0;

        $criticalZone = function() use($mutex, $wg, &$count) {
            static $init = [];

            $locked = (yield $mutex->tryLock());
            if ($locked) {
                try {
                    if (!isset($init["ip"])) {
                        $count++;
                        $init["ip"] = (yield Time::sleep(1));
                    }
                } finally {
                    yield $mutex->unlock();
                }
            }

            $wg->done();
        };


        for ($i = 0; $i < 10; $i++) {
            go($criticalZone);
        }

        yield $wg->wait();
        assert($count === 1);

        $g_wg->done();
    });

    yield $g_wg->wait();
    swoole_event_exit();
});