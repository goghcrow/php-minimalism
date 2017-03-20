<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/3/5
 * Time: 下午4:21
 */

namespace Minimalism\Test\A;

use function Minimalism\A\Client\async_sleep;
use function Minimalism\A\Core\spawn;
use function Minimalism\A\Core\race;

require __DIR__ . "/../../vendor/autoload.php";



// 两个均完成, 但 t2 更快
// 注意, t1 t2 都会被执行, 只会返回一个
spawn(function() {
    $start = microtime(true) * 1000;
    $r = (yield race([
        "t1" => function() {
            yield async_sleep(200);
            yield "finish t1";
        },
        "t2" => function() {
            yield async_sleep(100);
            yield "finish t2";
        },
    ]));
    assert($r === "finish t2");
    $duration = intval(microtime(true) * 1000 - $start);
    assert($duration >= 90 && $duration <= 110);
});


// 可以直接race IAsync 接口
spawn(function() {
    $start = microtime(true) * 1000;
    $r = (yield race([
        async_sleep(200),
        async_sleep(100)
    ]));
    $duration = intval(microtime(true) * 1000 - $start);
    assert($duration >= 90 && $duration <= 110);
});



// p3 更快, 所以先完成了
spawn(function() {
    yield race([
        function() {
            yield async_sleep(100);
            yield "finish t3";
        },
        function() {
            yield "start t4";
            yield async_sleep(200);
            throw new \Exception();
        },
    ]);
}, function($r, $ex = null) {
    assert($r === "finish t3");
    assert($ex === null);
});


// p6 更快, 所以失败了
spawn(function() {
    yield race([
        function() {
            yield async_sleep(200);
            yield "finish t5";
        },
        function() {
            yield "start t6";
            yield async_sleep(100);
            throw new \Exception();
        },
    ]);
}, function($r, $ex = null) {
    assert($r === null);
    assert($ex instanceof \Exception);
});




