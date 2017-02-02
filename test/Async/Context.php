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
        var_dump(yield Async::getCtx("foo"));
        yield Async::setCtx("hello", "world");
    });

    var_dump(yield Async::getCtx("hello"));
});