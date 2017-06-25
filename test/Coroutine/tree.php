<?php

namespace Minimalism\Test\Coroutine;

use function Minimalism\Coroutine\fork;
use function Minimalism\Coroutine\go;
use function Minimalism\Coroutine\self;
use Minimalism\Coroutine\Time;

require __DIR__ . "/../../vendor/autoload.php";


$task = go(function() {
    yield fork(function() {
        yield fork(function() {
            yield Time::sleep(100);
        });
    });
    yield fork(function() {
        yield fork(function() {
            yield fork(function() {
                yield fork(function() {
                    yield fork(function() {
                        yield;
                    });
                });
            });
            yield fork(function() {
                yield fork(function() {
                    yield fork(function() {
                        yield;
                    });
                    yield fork(function() {
                        // echo (yield self());
                        yield;
                    });
                });
            });
        });
    });
    yield fork(function() {
        yield;
    });
});


$a = <<<RAW
Completed *
 |-0:Completed
 | \-0:Running
 |-1:Completed
 | \-0:Completed
 |   |-0:Completed
 |   | \-0:Completed
 |   |   \-0:Completed
 |   \-1:Completed
 |     \-0:Completed
 |       |-0:Completed
 |       \-1:Completed
 \-2:Completed
RAW;


ob_start();
echo $task;
$b = ob_get_clean();

assert(trim($a) === trim($b));
