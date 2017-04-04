<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/1/31
 * Time: 下午4:34
 */

namespace Minimalism\Test\A;


use function Minimalism\A\Client\async_sleep;
use function Minimalism\A\Client\async_dns_lookup;
use function Minimalism\A\Client\async_curl_get;
use function Minimalism\A\Client\async_curl_post;
use function Minimalism\A\Client\async_curl_request;
use function Minimalism\A\Client\async_timeout;
use function Minimalism\A\Core\spawn;
use Minimalism\A\Core\Task;
use function Minimalism\A\Core\gen;
use function Minimalism\A\Core\cancelTask;
use function Minimalism\A\Core\getCtx;
use function Minimalism\A\Core\setCtx;
use Minimalism\A\Core\Exception\TaskCanceledException;

require __DIR__ . "/../../vendor/autoload.php";


function testAsyncTimeout()
{
    spawn(function() {
        $ex = null;
        try {
            yield async_timeout(function() {
                yield async_sleep(200);
            }, 100, new \Exception("timeout"));
        } catch (\Exception $ex) {

        }
        assert($ex instanceof \Exception && $ex->getMessage() === "timeout");

        $ex = null;
        try {
            yield async_timeout(function() {
                yield async_sleep(100);
            }, 200, new \Exception("timeout"));
        } catch (\Exception $ex) { }
        assert($ex === null);
    });
}
testAsyncTimeout();


function testCtorArgs()
{
    $k = function($r, $ex = null) {
        assert($r === "async");
        assert($ex === null);
    };

    spawn(function() {
        return "async";
    }, $k);

    // -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

    $k = function($r, $ex) {
        assert($r === null);
        assert($ex instanceof \Exception && $ex->getMessage() === "test ex");
    };

    spawn(function() {
        throw new \Exception("test ex");
    }, $k);

    // -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

    $k = function($r, $ex = null) {
        assert($r === 1);
        assert($ex === null);
    };
    spawn(1, $k);

    // -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

    $k = function($r, $ex = null) {
        assert($r === M_PI);
        assert($ex === null);
    };
    spawn(function() {
        yield M_PI;
    }, $k);

    // -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

    $k = function($r, $ex = null) {
        assert($r === null);
        assert($ex instanceof \Exception && $ex->getMessage() === "test ex");
    };
    spawn(function() {
        yield M_PI;
        throw new \Exception("test ex");
    }, $k);
}
testCtorArgs();


function testIAsync()
{
    // AsyncTask 自身实现 IAsync
    spawn(function() {
        $say = "";
        yield new Task(gen(function() use(&$say) {
            $say .= "Hello ";
            yield;
        }));
        $say .= "World!";
        assert($say === "Hello World!");
    });
}
testIAsync();


// 子任务
spawn(function() {
    $r = (yield gen(function() {
        $r1 = (yield async_dns_lookup("www.baidu.com"));
        $r2 = (yield async_dns_lookup("www.baidu.com"));
        yield [$r1, $r2];
    }));

    $r3 = (yield async_dns_lookup("www.baidu.com"));

    assert($r[0] === $r3);
    assert($r[1] === $r3);
});


// 取消任务 1. yield cancelTask()
spawn(function() {
    yield cancelTask();
    echo "unreached\n";
    assert(false);
});

// 取消任务 2. 抛出CancelTaskException
spawn(function() {
    yield;
    throw new TaskCanceledException();
    echo "unreached\n";
    assert(false);
});


// 上下文1
spawn(function() {
    yield setCtx("foo", "bar");
    yield gen(function() {
        assert((yield getCtx("foo")) === "bar");
        yield setCtx("hello", "world");
    });
    assert((yield getCtx("hello")) === "world");
});

// 上下文2
spawn(function() {
    yield gen(function() {
        $v = (yield getCtx("hello"));
        assert($v === "world");
    });
}, null, null, ["hello" => "world"]);


// example
spawn(function() {
    yield gen(function() {
        $r = (yield async_curl_get("www.baidu.com", 80));
        assert($r->statusCode === 200);
        $r->close();

        $r = (yield async_curl_post("www.baidu.com", 80, "/",
            ["Connection" => "close"],
            ["cookieK" => "cookieV"],
            "body", 2000));
        assert($r->statusCode === 302);
    });

    yield gen(function() {
        $ip = (yield async_dns_lookup("www.baidu.com"));

        $r = (yield async_curl_request($ip, 80, "PUT", "/",
            ["Connection" => "close"]));
        assert($r->statusCode === 200);
    });

    yield async_sleep(1000);

}, function($r, $e) {
    if ($e) {
        assert(false);
    }
});


swoole_timer_after(2000, function() { swoole_event_exit(); });