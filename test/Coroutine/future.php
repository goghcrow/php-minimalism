<?php

namespace Minimalism\Test\Coroutine;

use function Minimalism\Coroutine\chan;
use function Minimalism\Coroutine\future;
use Minimalism\Coroutine\FutureTask;
use function Minimalism\Coroutine\go;
use Minimalism\Coroutine\Time;

require __DIR__ . "/../../vendor/autoload.php";


swoole_timer_after(500, function() {
    swoole_event_exit();
});

function assert_between($var, $value, $scope)
{
    $r = $var >= ($value - $scope) && $var <= ($value + $scope);
    if ($r === false) {
        echo new \Exception();
    }
}


go(function() {
    $start = microtime(true);

    $ch = chan();

    go(function() use($ch) {
        yield Time::sleep(100);
        yield $ch->send();
    });

    yield Time::sleep(100);
    yield $ch->recv();

    // echo "cost ", microtime(true) - $start, "\n";

    $cost = microtime(true) - $start;
    assert_between($cost * 1000, 100, 10);
});



// channel 与 FutureTask 都可以达成的多协程同步
go(function() {
    $start = microtime(true);

    /** @var $future FutureTask */
    $future = (yield future(function() {
        yield Time::sleep(50);
        yield 42;
    }));

    yield Time::sleep(100);

    // 已经完成
    $r = (yield $future->get());
    assert($r === 42);

    // echo "cost ", microtime(true) - $start, "\n";
    $cost = microtime(true) - $start;
    assert_between($cost * 1000, 100, 10);
});


go(function() {
    $start = microtime(true);

    /** @var $future FutureTask */
    $future = (yield future(function() {
        yield Time::sleep(50);
        yield 42;
    }));

    // 阻塞等待
    $r = (yield $future->get());
    assert($r === 42);
    yield Time::sleep(100);

    // echo "cost ", microtime(true) - $start, "\n";
    $cost = microtime(true) - $start;
    assert_between($cost * 1000, 100 + 50, 10);
});



go(function() {
    $start = microtime(true);

    /** @var $future FutureTask */
    $future = (yield future(function() {
        yield Time::sleep(50);
        yield 42;
    }));

    // 阻塞等待 未超时
    $r = (yield $future->get(60));
    yield Time::sleep(100);
    assert($r === 42);

    // echo "cost ", microtime(true) - $start, "\n";
    $cost = microtime(true) - $start;
    assert_between($cost * 1000, 100 + 50, 10);
});


go(function() {
    $start = microtime(true);

    /** @var $future FutureTask */
    $future = (yield future(function() {
        yield Time::sleep(50);
        yield 42;
    }));

    // 阻塞等待超时
    $ex = null;
    try {
        $r = (yield $future->get(10));
        assert(false);
    } catch (\Exception $ex) {
        // echo "get result timeout\n";
    }
    assert($ex);

    yield Time::sleep(100);

    // echo "cost ", microtime(true) - $start, "\n";

    $cost = microtime(true) - $start;
    assert_between($cost * 1000, 100 + 10, 10);
});




go(function() {
    $start = microtime(true);

    /** @var $future FutureTask */
    $future = (yield future(function() {
        yield Time::sleep(50);
        yield 42;
        throw new \Exception();
    }));

    yield Time::sleep(100);

    // 已经完成
    $ex = null;
    try {
        $r = (yield $future->get());
        assert(false);
    } catch (\Exception $ex) {

    }
    assert($ex);

    // echo "cost ", microtime(true) - $start, "\n";

    $cost = microtime(true) - $start;
    assert_between($cost * 1000, 100, 10);
});


go(function() {
    $start = microtime(true);

    /** @var $future FutureTask */
    $future = (yield future(function() {
        yield Time::sleep(50);
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

    yield Time::sleep(100);

    // echo "cost ", microtime(true) - $start, "\n";

    $cost = microtime(true) - $start;
    assert_between($cost * 1000, 100 + 50, 10);
});
