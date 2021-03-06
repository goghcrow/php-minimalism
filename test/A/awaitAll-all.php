<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/18
 * Time: 下午9:31
 */

namespace Minimalism\Test\A;


use function Minimalism\A\Client\async_sleep;
use function Minimalism\A\Client\async_dns_lookup;
use function Minimalism\A\Client\async_curl_get;
use function Minimalism\A\Core\spawn;
use function Minimalism\A\Core\gen;
use function Minimalism\A\Core\waitAll;
use Minimalism\A\Core\Exception\AsyncTimeoutException;

require __DIR__ . "/../../vendor/autoload.php";

// exception
spawn(function() {
    $ex = null;
    try {
        $r = rand(1, 10);
        yield waitAll([
            async_sleep(100),
            async_sleep(200),
            async_dns_lookup("www.baidu_.com", 1), // ex
        ]);
    } catch (\Exception $ex) {

    }
    assert($ex instanceof AsyncTimeoutException);
});


spawn(function() {
    $ex = null;
    try {
        $r = (yield waitAll([
            async_dns_lookup("www.bing.com", 100),
            async_dns_lookup("www.so.com", 100),
            async_dns_lookup("www.baidu.com", 100),
        ]));
        assert(count($r) === 3);
        foreach ($r as $ip) {
            assert(ip2long($ip));
        }
    } catch (\Exception $ex) {
        assert(false);
    }
});


// 可以直接使用 IAsync 接口
spawn(function() {
    $start = microtime(true);
    (yield waitAll([
        async_sleep(1000),
        async_sleep(1000),
        async_sleep(1000),
    ]));
    assert((microtime(true) - $start) < 1.1);
});


spawn(function() {
    $start = microtime(true);
    $r = (yield waitAll([
            function() {
                yield async_sleep(1000);
                yield 'a';
            },
            function() {
                yield async_sleep(1000);
                yield 'b';
            },
            function() {
                yield async_sleep(1000);
                yield 'c';
            }]
    ));
    assert($r == ['a', 'b', 'c']);
    assert((microtime(true) - $start) < 1.1);
});


spawn(function() {
    $r = (yield waitAll([
            function() { yield 3; },
            function() { yield 2; },
            function() { yield 1; }
    ]));
    assert($r === [3, 2, 1]);

    $r = (yield waitAll([
            "x" => function() { yield 3; },
            "y" => function() { yield 2; },
            "z" => function() { yield 1; }]
    ));
    assert($r["x"] === 3);
    assert($r["y"] === 2);
    assert($r["z"] === 1);
});

spawn(function() {
    $ex = null;
    try {
        (yield waitAll([
                function() { yield 3; },
                function() { yield 2; },
                function() {
                    throw new \RuntimeException();
                    /** @noinspection PhpUnreachableStatementInspection */
                    yield;
                }
        ]));
        assert(false);
    } catch (\Exception $ex) {

    }
    assert($ex);
});


spawn(function() {
    try {
        $results = (yield waitAll([
            async_curl_get("www.baidu.com"),
            async_curl_get("www.baidu.com"),
            async_curl_get("www.baidu.com"),
        ]));

        foreach ($results as $result) {
            /* @var \swoole_http_client $result */
            // echo $result->body;
            assert(strlen($result->body));
        }
    } catch (\Exception $ex) {
        echo $ex;
    }
});

swoole_timer_after(1000, function() { swoole_event_exit(); });