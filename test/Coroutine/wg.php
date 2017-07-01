<?php

namespace Minimalism\Test\Coroutine;

use function Minimalism\Coroutine\callcc;
use function Minimalism\Coroutine\fork;
use function Minimalism\Coroutine\go;
use Minimalism\Coroutine\Time;
use function Minimalism\Coroutine\wg;

require __DIR__ . "/../../vendor/autoload.php";


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
    $wg = wg();

    $wg->add(1);

    $wg->done();

    yield $wg->wait();
});


go(function() {
    $wg = wg();

    $wg->add();

    yield fork(function() use($wg) {
        yield Time::sleep(1);
        $wg->done();

        yield Time::sleep(1);
        $wg->done();
    });

    yield $wg->wait();
});



go(function() {
    $wg = wg();

    $hosts = [
        "www.golang.org",
        "www.google.com",
        "www.youzan.com",
    ];

    foreach ($hosts as $host) {
        $wg->add(1);

        go(function() use($wg, $host) {
            try {
                $ip = (yield gethostbyname($host, 5));
            } catch (\Exception $e) {
                $ip = $e->getMessage();
            } finally {
                yield $wg->done();
            }

            // echo "$host ==> $ip\n";

            if ($ip === "gethostbyname timeout") {

            } else {
                assert(ip2long($ip));
            }

        });
    }

    yield $wg->wait();

    swoole_event_exit();
});