<?php
namespace Minimalism\Test\A;


use Minimalism\A\Client\AsyncSleep;
use function Minimalism\A\Core\async;
use Minimalism\A\Core\AsyncTask;
use function Minimalism\A\Core\await;
use Minimalism\A\Core\Exception\CancelTaskException;

require __DIR__ . "/../../vendor/autoload.php";


ob_start();

// 测试 异常跨异步回调传递
function testCatchGeneralException()
{
    $subTask = function() {
        yield "sub\n";
        throw new \Exception("ex");
    };

    $task = function() use($subTask) {
        yield 1;
        try {
            yield $subTask();
            assert(false);
        } catch (\Exception $ex) {
            echo "testCatchGeneralException";
            assert($ex->getMessage() === "ex");
        }
        yield 2;
    };

    $atask = new AsyncTask($task());
    $atask->begin(function($r, $ex) {
        assert($r === 2);
        assert($ex === null);
        if ($ex instanceof \Exception) {
            var_dump($ex->getMessage());
        }
        echo "\tDONE\n";
    });
}
testCatchGeneralException();


// (server)测试回调中未捕获异常不会抛出fatal error
// 通过complete回调获取异常
function testGeneralException()
{
    $task = function() {
        yield 1;
        assert(true);
        throw new \Exception("general exception");
        /** @noinspection PhpUnreachableStatementInspection */
        assert(false);
        yield 2;
    };

    $atask = new AsyncTask($task());
    $atask->begin(function($r, $ex) {
        // 普通异常仍然会调用complete
        assert($r === null);
        assert($ex instanceof \Exception && $ex->getMessage() === "general exception");
        echo "testGeneralException\tDONE\n";
    });
}

testGeneralException();


// 测试通过异常终止迭代流程
function testCancelException()
{
    $task = function() {
        yield 1;
        throw new CancelTaskException("cancel task exception");
        /** @noinspection PhpUnreachableStatementInspection */
        assert(false);
        yield 2;
    };

    $atask = new AsyncTask($task());
    $atask->begin(function($r, $ex) {
        assert($ex instanceof CancelTaskException);
        assert($r === null);
        echo "testCancelException\tDONE\n";
    });
}
testCancelException();


// 测试通过异常终止迭代流程
// CancelTaskException是约定的特殊异常， 无法正常捕获
function testCatchCancelException()
{
    $subTask = function() {
        yield "sub\n";
        throw new CancelTaskException("CancelTaskException");
    };

    $task = function() use($subTask) {
        yield 1;
        try {
            yield $subTask();
        } catch (\Exception $ex) {
            assert(false);
            // 此处无法捕获CancelTaskException任务终止
            echo $ex->getMessage(), "\n";
        }
        assert(false);
        yield 2;
    };

    $atask = new AsyncTask($task());
    $atask->begin(function($r, $ex) {
        assert($ex instanceof CancelTaskException);
        assert($r === null);
        echo "testCatchCancelException\tDONE\n";
    });
}
testCatchCancelException();



// 测试task嵌套与返回值
function nestedTask()
{
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


    $atask = new AsyncTask($task());
    $atask->begin(function($r, $ex) use($nestedR) {
        assert($r === $nestedR);
        assert($ex === null);
        echo "nestedTask\tDONE\n";
    });
}
nestedTask();




// 测试 嵌套 asyncTask与返回值
function nestedAsyncTask()
{
    $nestedR = rand();

    $task = function () use($nestedR) {
        yield 1;

        $nestedTask = function () use($nestedR) {
            yield $nestedR;
        };
        $r = (yield new AsyncTask($nestedTask()));
        assert($r === $nestedR);
    };
    $atask = new AsyncTask($task());
    $atask->begin(function($r, $ex) use($nestedR) {
        assert($r === $nestedR);
        assert($ex === null);
        echo "nestedAsyncTask\tDONE\n";
    });
}

nestedAsyncTask();

assert(ob_get_clean() ===
    <<<R
testCatchGeneralException	DONE
testGeneralException	DONE
testCancelException	DONE
testCatchCancelException	DONE
nestedTask	DONE
nestedAsyncTask	DONE

R
);



// 测试多层嵌套与返回值
function nestedTask2()
{
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
        yield new AsyncSleep(1);
        $r = (yield t2());
        assert($r === "t2");
        yield "t3";
    }

    $cb = function($r, $ex) {
        assert($r === "t3");
        if ($ex instanceof \Exception) {
            var_dump($ex->getMessage());
        }
        // echo "nestedTask2\tDONE\n";
    };
    $task = new AsyncTask(t3());
    $task->begin($cb);
}
nestedTask2();