<?php

namespace Minimalism\Test\Coroutine;

use function Minimalism\Coroutine\fork;
use function Minimalism\Coroutine\getctx;
use function Minimalism\Coroutine\go;
use function Minimalism\Coroutine\getTask;
use function Minimalism\Coroutine\setctx;

require __DIR__ . "/../../vendor/autoload.php";


go(function() {
    yield setctx("id", 42);

    yield fork(function() {
        $id = (yield getctx("id"));
        assert($id === 42);
    });
});

// equals

go(function() {
    yield setctx("id", 42);

    $self = (yield getTask());
    $parent = $self();

    // 不需要yield, 显示传递 parent
    go(function() {
        $id = (yield getctx("id"));
        assert($id === 42);
    }, null, $parent);
});


// and return

