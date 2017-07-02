<?php

namespace Minimalism\Test\Coroutine;

use function Minimalism\Coroutine\go;
use function Minimalism\Coroutine\getTask;
use Minimalism\Coroutine\Task;

require __DIR__ . "/../../vendor/autoload.php";


Task::setUnObservedExceptionHandler(function($r, $ex) {
    // 忽略task异常
    // echo $ex;
});

go(function() {
    /** @var Task $task */
    $task = (yield getTask());

    swoole_timer_after(1, function() use($task) {
        assert($task->isFaulted()); // task fault !!!
    });

    throw new \Exception();
});

