<?php

namespace Minimalism\Test\Coroutine;

use function Minimalism\Coroutine\go;
use function Minimalism\Coroutine\getTask;
use Minimalism\Coroutine\Time;

require __DIR__ . "/../../vendor/autoload.php";


$_ = function() {

    function ping($val)
    {
        echo yield getTask(); // 查看 task tree

        echo "ping: $val\n";
        yield Time::sleep(500);
        yield pong($val + 1);
    }

    function pong($val)
    {
        echo "pong: $val\n";
        yield Time::sleep(500);
        yield ping($val + 1);
    }

    go(function() {
        yield ping(0);
    });

    go(function() {
        yield ping(0);
    });
};

//$_();