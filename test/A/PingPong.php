<?php

namespace Minimalism\Test\A;


use function Minimalism\A\Client\async_sleep;
use function Minimalism\A\Core\spawn;

require __DIR__ . "/../../vendor/autoload.php";

function ping($val)
{
    echo "ping: $val\n";
    yield async_sleep(500);
    yield pong($val + 1);
}

function pong($val)
{
    echo "pong: $val\n";
    yield async_sleep(500);
    yield ping($val + 1);
}

spawn(function() {
    yield ping(0);
});

spawn(function() {
    yield ping(0);
});