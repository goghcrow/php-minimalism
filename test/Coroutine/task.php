<?php

namespace Minimalism\Test\Coroutine;

use Minimalism\Coroutine\CancelTaskException;
use function Minimalism\Coroutine\go;
use Minimalism\Coroutine\Syscall;
use Minimalism\Coroutine\Task;
use Minimalism\Coroutine\Time;

require __DIR__ . "/../../vendor/autoload.php";



// !!! syscall 内部支持 生成器函数
call_user_func(function() {
    go(function() {
        $r = (yield new Syscall(function(Task $task) {
            yield 42;
        }));
        assert($r === 42);
    });
});

// 测试 异常跨异步回调传递
call_user_func(function() {
    $subTask = function() {
        throw new \Exception("ex");
    };

    $task = function() use($subTask) {
        $ex = null;
        yield 1;
        try {
            yield $subTask();
            assert(false);
        } catch (\Exception $ex) { }
        assert($ex->getMessage() === "ex");
        yield 2;
    };

    $atask = new Task($task());
    $atask->start(function($r, $ex) {
        assert($r === 2);
        assert($ex === null);
    });
});


// 测试回调中未捕获异常不会抛出fatal error
// 通过complete回调获取异常
call_user_func(function() {
    $task = function() {
        yield 1;
        throw new \Exception("general exception");
        /** @noinspection PhpUnreachableStatementInspection */
        assert(false);
        yield 2;
    };

    $atask = new Task($task());
    $atask->start(function($r, $ex) {
        // 普通异常仍然会调用complete
        assert($r === null);
        assert($ex instanceof \Exception && $ex->getMessage() === "general exception");
    });
});


// 测试通过异常终止迭代流程
call_user_func(function() {
    $task = function() {
        yield 1;
        throw new CancelTaskException("cancel task exception");
        /** @noinspection PhpUnreachableStatementInspection */
        assert(false);
        yield 2;
    };

    $atask = new Task($task());
    $atask->start(function($r, $ex) {
        assert($ex instanceof CancelTaskException);
        assert($r === null);
    });
});



// 测试通过异常终止迭代流程
// CancelTaskException是约定的特殊异常， 无法正常捕获
call_user_func(function() {
    $subTask = function() {
        yield "sub\n";
        throw new CancelTaskException("CancelTaskException");
    };

    $task = function() use($subTask) {
        yield 1;
        try {
            yield $subTask();
        } catch (\Exception $ex) {
            // 此处无法捕获CancelTaskException任务终止
            assert(false);
        }
        assert(false);
        yield 2;
    };

    $atask = new Task($task());
    $atask->start(function($r, $ex) {
        assert($ex instanceof CancelTaskException);
        assert($r === null);
    });
});


// 测试task嵌套与返回值
call_user_func(function() {
    $nestedR = rand();

    $task = function() use($nestedR) {
        $task = function () use($nestedR) {
            yield 1;
            $nestedTask = function () use($nestedR) {
                yield $nestedR;
            };
            yield $nestedTask();
        };

        $r = (yield $task());
        assert($r === $nestedR);
    };


    $atask = new Task($task());
    $atask->start(function($r, $ex) use($nestedR) {
        assert($r === $nestedR);
        assert($ex === null);
    });
});



// 测试 嵌套Task与返回值
call_user_func(function() {
    $nestedR = rand();

    $task = function () use($nestedR) {
        yield 1;

        $nestedTask = function () use($nestedR) {
            yield $nestedR;
        };
        $r = (yield new Task($nestedTask()));
        assert($r === $nestedR);
    };
    $atask = new Task($task());
    $atask->start(function($r, $ex) use($nestedR) {
        assert($r === $nestedR);
        assert($ex === null);
    });
});



// 测试多层嵌套与返回值
call_user_func(function() {
    function t1()
    {
        yield "t1";
    }

    function t2()
    {
        $r = (yield t1());
        assert($r === "t1");
        yield "t2";
    }

    function t3()
    {
        yield Time::sleep(1);
        $r = (yield t2());
        assert($r === "t2");
        yield "t3";
    }

    $cb = function($r, $ex) {
        assert($r === "t3");
        if ($ex instanceof \Exception) {
            assert(false);
        }
    };
    $task = new Task(t3());
    $task->start($cb);
});



// unObservedExceptionHandler
call_user_func(function() {
    Task::setUnObservedExceptionHandler(function($r, \Exception $ex, Task $sender) {
        assert($ex->getMessage() === "unObservedExceptionHandler");
    });

    go(function() {
        throw new \Exception("unObservedExceptionHandler");
    });
});


