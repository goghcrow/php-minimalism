<?php

namespace Minimalism\Test\Coroutine;

use Minimalism\Coroutine\Channel\Channel;
use function Minimalism\Coroutine\go;
use Minimalism\Coroutine\Time;

require __DIR__ . "/../../vendor/autoload.php";


go(function() {
    $start = Time::ms();

    /** @var Channel $chan */
    $chan = Time::after(1000);

    // 只读
    try {
        yield $chan->send();
        assert(false);
    } catch (\Exception $e) {
        assert($e instanceof \BadMethodCallException);
    }

    // do something else ....


    // 阻塞到定时器到时
    yield $chan->recv();

    $end = Time::ms();

    $du = $end - $start;
    assert($du >= 999 && $du <= 1001);
});