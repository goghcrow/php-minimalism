<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/2
 * Time: 上午10:46
 */

namespace Minimalism\Test\AsyncTask;

use Minimalism\Async\Async;

require __DIR__ . "/../../vendor/autoload.php";

Async::exec(function() {
    yield Async::coroutine(function() {
        echo (yield Async::dns("www.baidu.com")), "\n";
        echo (yield Async::dns("www.baidu.com")), "\n";
    });

    echo (yield Async::dns("www.baidu.com")), "\n";
});


Async::exec(function() {
    $task = function() {
        echo (yield Async::dns("www.baidu.com")), "\n";
        echo (yield Async::dns("www.baidu.com")), "\n";
    };

    yield $task();
    echo (yield Async::dns("www.baidu.com")), "\n";
});