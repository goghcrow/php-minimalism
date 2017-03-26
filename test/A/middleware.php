<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/3/5
 * Time: 下午11:55
 */

namespace Minimalism\Test\A;

use function Minimalism\A\Client\async_sleep;
use function Minimalism\A\Core\spawn;
use function Minimalism\A\Core\await;
use function Minimalism\A\Server\Http\compose;
use Minimalism\A\Server\Http\Middleware\RequestTimeout;

require __DIR__ . "/../../vendor/autoload.php";


spawn(function() {
    $ex = null;
    try {
        yield await(compose([
            new RequestTimeout(100, new \Exception("timeout")),
            function($ctx, $next) {
                yield $next;
                yield async_sleep(200);
            }
        ]));
    } catch (\Exception $ex) {

    }
    assert($ex instanceof \Exception && $ex->getMessage() === "timeout");

    $ex = null;
    try {
        yield await(compose([
            new RequestTimeout(200, new \Exception("timeout")),
            function($ctx, $next) {
                yield $next;
                yield async_sleep(100);
                swoole_event_exit();
            }
        ]));
    } catch (\Exception $ex) {

    }
    assert($ex === null);
});