<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/16
 * Time: 上午2:40
 */

namespace Minimalism\Test\AsyncTask;

use Minimalism\Async\Async;
use Minimalism\Async\Core\CallCC;

require __DIR__ . "/../../vendor/autoload.php";


Async::exec(function() {
    $r = (yield Async::callcc(function($k) {
        $r = "hello world";
        $ex = null;
        $k($r, $ex);
    }));

    assert($r === "hello world");
});


Async::exec(function() {
    try {
        $r = (yield Async::callcc(function($k) {
            $r = null;
            $ex = new \RuntimeException("hello ex");
            $k($r, $ex);
        }));
        assert(false);
    } catch (\Exception $ex) {
        assert($ex->getMessage() === "hello ex");
    }
});




Async::exec(function() {
    // 或者 $r = (yield Async::callcc(function($k) {...
    $r = (yield new CallCC(function($k) {
        swoole_timer_after(1000, function() use($k) {
            $k("返回值");
        });
    }));

    var_dump($r);
});



function asyncSleep($ms)
{
    return new CallCC(function($k) use($ms) {
       swoole_timer_after($ms, function() use($k) {
           $k();
       });
    });
}

Async::exec(function() {
    yield asyncSleep(1000);
    echo "sleep 1000ms\n";
});

/** @var callable */
$K = null;
Async::exec(function() {
    // 或者 $r = (yield Async::callcc(function($k) {...
    $r = (yield new CallCC(function($k) {
        swoole_timer_after(1000, function() use($k) {
            global $K;
            $K = $k;
        });
    }));

    var_dump($r);
});


swoole_timer_after(2000, function() {
    global $K;
    //
    $K("wa ha ha");
});