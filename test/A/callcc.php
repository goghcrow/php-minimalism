<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/16
 * Time: 上午2:40
 */

namespace Minimalism\Test\A;


use function Minimalism\A\Core\spawn;
use function Minimalism\A\Core\callcc;
use Minimalism\A\Core\Exception\AsyncTimeoutException;

require __DIR__ . "/../../vendor/autoload.php";


// test return
spawn(function() {
    $r = (yield callcc(function($k) {
        $r = "hello world";
        $ex = null;
        $k($r, $ex);
    }));
    assert($r === "hello world");
});


// test exception
spawn(function() {
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
spawn(function() {
    $ip = (yield callcc(function($k) {
        swoole_async_dns_lookup("www.baidu.com", function($host, $ip) use($k) {
            $k($ip);
        });
    }));
}, function($r, $ex) {
    assert($ex === null);
});

spawn(function() {
    $r = (yield callcc(function($k) {
        swoole_timer_after(100, function() use($k) {
            $k("done");
        });
    }));

    assert($r === "done");
});

// test timeout
spawn(function() {
    $ex = null;
    try {
        $r = (yield callcc(function($k) {
            swoole_async_dns_lookup("www.baidusas.com", function($host, $ip) use($k) {
                $k($ip);
            });
        }, 1));
        assert(false);
    } catch (AsyncTimeoutException $ex) {

    }
    assert($ex);
}, function($r, $e) {
    static $once = true;
    if ($once) {
        $once = false;
    } else {
        assert(false);
    }
});



spawn(function() {
    $k = (yield callcc(function($k) {
            $k($k);
    }));
});