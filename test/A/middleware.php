<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/3/5
 * Time: 下午11:55
 */

namespace Minimalism\Test\A;

use function Minimalism\A\Client\async_sleep;
use function Minimalism\A\Core\async;
use function Minimalism\A\Core\await;
use function Minimalism\A\Server\Http\compose;
use Minimalism\A\Server\Http\Middleware\Timeout;

require __DIR__ . "/../../vendor/autoload.php";




async(function() {
    $ex = null;
    try {
        yield await(compose([
            new Timeout(100, new \Exception("timeout")),
            function($ctx, $next) {
                yield $next;
                yield async_sleep(200);
            },
        ]));
    } catch (\Exception $ex) {

    }
    assert($ex instanceof \Exception && $ex->getMessage() === "timeout");


    $ex = null;
    try {
        yield await(compose([
            new Timeout(200, new \Exception("timeout")),
            function($ctx, $next) {
                yield $next;
                yield async_sleep(100);
            },
        ]));
    } catch (\Exception $ex) {

    }
    assert($ex === null);
});