<?php

namespace Minimalism\Test\Coroutine;

use function Minimalism\Coroutine\chan;
use function Minimalism\Coroutine\go;

require __DIR__ . "/../../vendor/autoload.php";


call_user_func(function() {
    $ch = chan();
    $ch->close();
    try {
        $ch->close();
        assert(false);
    } catch (\Exception $e) {}
    assert(isset($e));
});


call_user_func(function() {
    $ch = chan(10);
    $ch->close();
    try {
        $ch->close();
        assert(false);
    } catch (\Exception $e) {}
    assert(isset($e));
});


// 往已经close的chan发送数据
go(function() {
    $ch = chan();
    $ch->close();

    try {
        yield $ch->send();
        assert(false);
    } catch (\Exception $e) {}
    assert(isset($e));

    try {
        yield $ch->send();
        assert(false);
    } catch (\Exception $e) {}
    assert(isset($e));
});

go(function() {
    $ch = chan(10);
    $ch->close();

    try {
        yield $ch->send();
        assert(false);
    } catch (\Exception $e) {}
    assert(isset($e));

    try {
        yield $ch->send();
        assert(false);
    } catch (\Exception $e) {}
    assert(isset($e));
});

go(function() {
    $ch = chan();

    go(function() use($ch) {
        try {
            yield $ch->send();
            assert(false);
        } catch (\Exception $e) {}
        assert(isset($e));
    });
    $ch->close();
});


go(function() {
    $ch = chan(1);

    go(function() use($ch) {
        yield $ch->send();

        try {
            yield $ch->send();
            assert(false);
        } catch (\Exception $e) {}
        assert(isset($e));
    });

    $ch->close();
});





// recv已经close的channel
go(function() {
    $ch = chan();
    $ch->close();

    list($recv, $ok) = (yield $ch->recv());
    assert($recv === null);
    assert($ok === false);

    list($recv, $ok) = (yield $ch->recv());
    assert($recv === null);
    assert($ok === false);
});

go(function() {
    $ch = chan(10);
    $ch->close();

    list($recv, $ok) = (yield $ch->recv());
    assert($recv === null);
    assert($ok === false);

    list($recv, $ok) = (yield $ch->recv());
    assert($recv === null);
    assert($ok === false);
});


go(function() {
    $ch = chan();

    go(function() use($ch) {
        list($recv, $ok) = (yield $ch->recv());
        assert($recv === null);
        assert($ok === false);
    });
    $ch->close();
});

go(function() {
    $ch = chan(1);
    yield $ch->send();

    go(function() use($ch) {
        yield $ch->recv();

        list($recv, $ok) = (yield $ch->recv());
        assert($recv === null);
        assert($ok === false);
    });
    $ch->close();
});
