<?php

namespace Minimalism\Test\Coroutine;

use function Minimalism\Coroutine\defer;
use function Minimalism\Coroutine\go;
use function Minimalism\Coroutine\kill;

require __DIR__ . "/../../vendor/autoload.php";



$task = go(function() {
    ob_start();

    try {
        yield kill();
        echo 2;
    } finally {
//        echo "finally";
    }
    echo 3;
});// $task->cancel();


go(function() {
    yield defer(function() {
//       echo "defer";
    });

    yield kill();
    echo 4;
});

swoole_timer_after(1000, function() {
    // 定时器影响 finally 与 defer
});