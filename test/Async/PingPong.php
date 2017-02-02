<?php

namespace Minimalism\Test\AsyncTask;

use Minimalism\Async\Async;

require __DIR__ . "/../../vendor/autoload.php";

function ping($val)
{
    echo "ping: $val\n";
    yield Async::sleep(500);
    yield pong($val + 1);
}

function pong($val)
{
    echo "pong: $val\n";
    yield Async::sleep(500);
    yield ping($val + 1);
}

Async::exec(function() {
    yield ping(0);
});