<?php

namespace Minimalism\Test\Coroutine;

use Minimalism\Coroutine\AsyncTimeoutException;
use function Minimalism\Coroutine\callcc;
use function Minimalism\Coroutine\go;

require __DIR__ . "/../../vendor/autoload.php";


// test return
go(function() {
    $r = (yield callcc(function($k) {
        $r = "hello world";
        $ex = null;
        $k($r, $ex);
    }));
    assert($r === "hello world");
});


// test exception
go(function() {
    try {
        $r = (yield callcc(function($k) {
            $r = null;
            $ex = new \RuntimeException("hello ex");
            $k($r, $ex);
        }));
        assert(false);
    } catch (\Exception $ex) {
        assert($ex->getMessage() === "hello ex");
    }
});


// example
go(function() {
    $ip = (yield callcc(function($k) {
        \swoole_async_dns_lookup("www.baidu.com", function($host, $ip) use($k) {
            $k($ip);
        });
    }));
    assert(ip2long($ip));
}, function($r, $ex) {
    assert($ex === null);
});


go(function() {
    $r = (yield callcc(function($k) {
        swoole_timer_after(100, function() use($k) {
            $k("done");
        });
    }));

    assert($r === "done");
});


// test timeout
go(function() {
    $ex = null;
    try {
        yield callcc(function($k) {
            swoole_timer_after(100, function() use($k) {
                $k(false);
            });
        }, 1);
        assert(false);
    } catch (AsyncTimeoutException $ex) {}
    assert($ex);
}, function($r, $e) {
    static $once = true;
    if ($once) {
        $once = false;
    } else {
        assert(false);
    }
});



go(function() {
    $k = (yield callcc(function($k) {
        $k($k);
    }));

    // 无法向 scheme 时光倒流
    // Cannot resume an already running generator
    // $k();
});