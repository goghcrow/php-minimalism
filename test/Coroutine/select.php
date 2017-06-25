<?php

namespace Minimalism\Test\Coroutine;

use function Minimalism\Coroutine\chan;
use function Minimalism\Coroutine\go;
use function Minimalism\Coroutine\select;
use Minimalism\Coroutine\Time;

require __DIR__ . "/../../vendor/autoload.php";


go(function() {
    $ch1 = chan();
    $timeout = Time::after(1000);

    $r = (yield select([
        $ch1,
        $timeout,
    ]));
    assert($r === Time::TIMEOUT);
});

