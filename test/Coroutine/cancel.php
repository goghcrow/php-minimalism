<?php

namespace Minimalism\Test\Coroutine;

use function Minimalism\Coroutine\await;
use function Minimalism\Coroutine\cancelTask;
use Minimalism\Coroutine\CancelTaskException;
use function Minimalism\Coroutine\go;
use Minimalism\Coroutine\Task;

require __DIR__ . "/../../vendor/autoload.php";


go(function() {
    yield cancelTask();
    assert(false);
}, function($r, $ex, Task $task) {
    assert($task->isCanceled());
    assert($ex instanceof CancelTaskException);
});


go(function() {
    yield;
    throw new CancelTaskException();
}, function($r, $ex, Task $task) {
    assert($ex instanceof CancelTaskException);
});


go(function() {
    yield await(function() {
        yield await(function() {
            // 取消 task tree
            yield cancelTask();
            assert(false);
        });
        assert(false);
    });
    assert(false);
}, function($r, $ex, Task $task) {
    assert($ex instanceof CancelTaskException);
    assert($task->isCanceled());
});


// 如果想取消单个task， 可以向外抛异常

go(function() {
    $r = (yield await(function() {
        try {
            yield await(function() {
                yield;
                throw new \Exception();
            });
        } catch (\Exception $ex) { }
        yield 42;
    }));
    yield $r;
}, function($r, $ex, Task $task) {
    assert($r === 42);
});


go(function() {
    yield await(function() {
        yield await(function() {
            yield cancelTask();
            assert(false);
        });
        assert(false);
    });
    assert(false);
}, function($r, $ex, Task $task) {
    // continuation 可以判断任务是否被取消了
    assert($task->isCanceled());
});



go(function() {
   yield cancelTask();
});

Task::setUnObservedExceptionHandler(function($r, $ex, Task $task) {
    // CancelTaskException 未处理不会进入 全局task未捕获异常处理器
    assert(false);
});