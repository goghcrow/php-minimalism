<?php

namespace Minimalism\Test\Validation;

use Zan\Framework\Foundation\Coroutine\Task;

require __DIR__ . "/../../src/Coroutine/Scheduler.php";
require __DIR__ . "/../../src/Coroutine/Task.php";
require __DIR__ . "/../../src/Coroutine/Context.php";
require __DIR__ . "/../../src/Coroutine/Event.php";
require __DIR__ . "/../../src/Coroutine/EventChain.php";
require __DIR__ . "/../../src/Coroutine/Signal.php";

function emptyTask() {
    if (false) {
        yield 1;
    }
}
//$g = emptyTask();
//try {
//    var_dump($g->throw(new \Exception()));
//} catch (\Exception $e) {
//    var_dump($e);
//}
//exit;

//for(
//    $cur = $g->current();
//    $g->valid();
//    $cur = $g->send(null)) {
//    var_dump($cur);
//}
//exit;


/*
function t() {
    yield 1;
    yield 2;
}

$g = t();

for(
    $cur = $g->current(); $g->valid(); $cur = $g->send(null)) {
    var_dump($cur);
}

$g = t();

$cur = $g->current();
if ($g->valid()) { var_dump($cur); }

$cur = $g->send(1);
if ($g->valid()) { var_dump($cur); }
*/

exit;
$t = function() {
  $r = (yield);
    var_dump($r);
};

$g = $t();
$g->current();
$g->send("hello");
exit;

$tt = function() {
    $task = function () {
        yield 1;
        echo 1;

        $nestedTask = function () {
            yield 2;
            echo 2;
        };
        yield $nestedTask();
    };

    $ret = (yield $task());
    var_dump($ret);
};


Task::execute($tt());