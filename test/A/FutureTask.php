<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/3/26
 * Time: 下午3:08
 */

namespace Minimalism\Test\A;


use function Minimalism\A\Client\delay;
use function Minimalism\A\Core\chan;
use function Minimalism\A\Core\fork;
use Minimalism\A\Core\FutureTask;
use function Minimalism\A\Core\spawn;

require __DIR__ . "/../../vendor/autoload.php";


function assert_between($var, $value, $scope)
{
    assert($var >= ($value - $scope) && $var <= ($value + $scope));
}


///*
spawn(function() {
    $start = microtime(true);

    $ch = chan();

    spawn(function() use($ch) {
        yield delay(1000);
        yield $ch->send();
    });

    yield delay(1000);
    yield $ch->recv();

    // echo "cost ", microtime(true) - $start, "\n";

    $cost = microtime(true) - $start;
    assert_between($cost * 1000, 1000, 50);
});
//*/


// channel 与 FutureTask 都可以达成的多协程同步


swoole_timer_after(2000, function() {
    swoole_event_exit();
});


///*
spawn(function() {
    $start = microtime(true);

    /** @var $future FutureTask */
    $future = (yield fork(function() {
        yield delay(500);
        yield 42;
    }));

    yield delay(1000);

    // 已经完成
    $r = (yield $future->get());
    assert($r === 42);

    // echo "cost ", microtime(true) - $start, "\n";
    $cost = microtime(true) - $start;
    assert_between($cost * 1000, 1000, 50);
});
//*/

///*
spawn(function() {
    $start = microtime(true);

    /** @var $future FutureTask */
    $future = (yield fork(function() {
        yield delay(500);
        yield 42;
    }));

    // 阻塞等待
    $r = (yield $future->get());
    yield delay(1000);
    assert($r === 42);

    // echo "cost ", microtime(true) - $start, "\n";
    $cost = microtime(true) - $start;
    assert_between($cost * 1000, 1000 + 500, 50);
});
//*/


///*
spawn(function() {
    $start = microtime(true);

    /** @var $future FutureTask */
    $future = (yield fork(function() {
        yield delay(500);
        yield 42;
    }));

    // 阻塞等待 未超时
    $r = (yield $future->get(600));
    yield delay(1000);
    assert($r === 42);

    // echo "cost ", microtime(true) - $start, "\n";
    $cost = microtime(true) - $start;
    assert_between($cost * 1000, 1000 + 500, 50);
});
//*/



///*
spawn(function() {
    $start = microtime(true);

    /** @var $future FutureTask */
    $future = (yield fork(function() {
        yield delay(500);
        yield 42;
    }));

    // 阻塞等待超时
    $ex = null;
    try {
        $r = (yield $future->get(100));
        var_dump($r);
    } catch (\Exception $ex) {
        // echo "get result timeout\n";
    }
    assert($ex);

    yield delay(1000);

    // echo "cost ", microtime(true) - $start, "\n";

    $cost = microtime(true) - $start;
    assert_between($cost * 1000, 1000 + 100, 50);
});
//*/


///*
spawn(function() {
    $start = microtime(true);

    /** @var $future FutureTask */
    $future = (yield fork(function() {
        yield delay(500);
        yield 42;
        throw new \Exception();
    }));

    yield delay(1000);

    // 已经完成
    $ex = null;
    try {
        $r = (yield $future->get());
        var_dump($r);
    } catch (\Exception $ex) {

    }
    assert($ex);

    // echo "cost ", microtime(true) - $start, "\n";

    $cost = microtime(true) - $start;
    assert_between($cost * 1000, 1000, 50);
});
//*/





///*
spawn(function() {
    $start = microtime(true);

    /** @var $future FutureTask */
    $future = (yield fork(function() {
        yield delay(500);
        yield 42;
        throw new \Exception();
    }));

    // 阻塞等待
    $ex = null;
    try {
        $r = (yield $future->get());
        assert(false);
    } catch (\Exception $ex) {
    }
    assert($ex);

    yield delay(1000);

    // echo "cost ", microtime(true) - $start, "\n";

    $cost = microtime(true) - $start;
    assert_between($cost * 1000, 1000 + 500, 50);
});
//*/