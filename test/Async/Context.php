<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/2
 * Time: 下午6:45
 */

namespace Minimalism\Test\AsyncTask;

use Minimalism\Async\Async;

require __DIR__ . "/../../vendor/autoload.php";

Async::exec(function() {
    yield Async::setCtx("foo", "bar");
    yield Async::coroutine(function() {
        assert((yield Async::getCtx("foo")) === "bar");
        yield Async::setCtx("hello", "world");
    });
    assert((yield Async::getCtx("hello")) === "world");
});