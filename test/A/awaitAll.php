<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/18
 * Time: 下午9:31
 */

namespace Minimalism\Test\A;


use function Minimalism\A\Client\async_sleep;
use function Minimalism\A\Client\async_dns_loohup;
use function Minimalism\A\Client\async_get;
use function Minimalism\A\Core\async;
use function Minimalism\A\Core\await;
use function Minimalism\A\Core\awaitAll;

require __DIR__ . "/../../vendor/autoload.php";


async(function() {
    $start = microtime(true);
    (yield awaitAll([
        await(function() {
            yield async_sleep(1000);
        }),
        await(function() {
            yield async_sleep(1000);
        }),
        await(function() {
            yield async_sleep(1000);
        })]
    ));
    assert((microtime(true) - $start) < 1.1);
});


async(function() {
    $r = (yield awaitAll([
            await(function() {
                yield 3;
            }),
            await(function() {
                yield 2;
            }),
            await(function() {
                yield 1;
            })]
    ));
    assert($r === [3, 2, 1]);

    $r = (yield awaitAll([
            "x" => await(function() {
                yield 3;
            }),
            "y" => await(function() {
                yield 2;
            }),
            "z" => await(function() {
                yield 1;
            })]
    ));
    assert($r["x"] === 3);
    assert($r["y"] === 2);
    assert($r["z"] === 1);
});

async(function() {
    $ex = null;
    try {
        (yield awaitAll([
            await(function() {
                yield 1;
            }),
            await(function() {
                yield 1;
            }),
            await(function() {
                throw new \RuntimeException();
                /** @noinspection PhpUnreachableStatementInspection */
                yield;
            })]
        ));
        assert(false);
    } catch (\Exception $ex) {

    }
    assert($ex);
});


async(function() {
    try {
        $results = (yield awaitAll([
            function() {
                $ip = (yield async_dns_loohup("www.baidu.com"));
                yield async_get($ip);
            },
            function() {
                $ip = (yield async_dns_loohup("www.baidu.com"));
                yield async_get($ip);
            },
            function() {
                $ip = (yield async_dns_loohup("www.baidu.com"));
                yield async_get($ip);
            }]
        ));


        foreach ($results as $result) {
            /* @var \swoole_http_client $result */
            echo $result->body;
            assert(strlen($result->body));
        }
    } catch (\Exception $ex) {
        echo $ex;
    }
});

swoole_timer_after(1000, function() { swoole_event_exit(); });