<?php

namespace Minimalism\Test\Coroutine;

use function Minimalism\Coroutine\fork;
use function Minimalism\Coroutine\getctx;
use function Minimalism\Coroutine\go;
use function Minimalism\Coroutine\setctx;

require __DIR__ . "/../../vendor/autoload.php";

$task = null;
go(function() use(&$task) {
    yield setctx("id", 42);

    yield fork(function() {
        $id = (yield getctx("id"));
        assert($id === 42);
    });
});