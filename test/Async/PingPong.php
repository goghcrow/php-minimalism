<?php

namespace Minimalism\Test\AsyncTask;

use Minimalism\Async\AsyncSleep;
use Minimalism\Async\Core\AsyncTask;

require __DIR__ . "/../../vendor/autoload.php";

// ping pong, pong ping, ping pong, pong ping

function ping()
{
    while (true) {
        echo "ping\n";
        yield new AsyncSleep(1000);
    }
}

function pong()
{
    while (true) {
        echo "pong\n";
        yield new AsyncSleep(1000);
    }
}

(new AsyncTask(ping()))->start();
(new AsyncTask(pong()))->start();