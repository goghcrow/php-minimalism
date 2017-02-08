<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/1/31
 * Time: 下午4:34
 */

namespace Minimalism\Test\AsyncTask;

use Minimalism\Async\Async;

require __DIR__ . "/../../vendor/autoload.php";



Async::exec(function() {

    $task = function() {
        $ip = (yield Async::dns("www.baidu.com"));

        $r = (yield Async::get($ip, 80));
        var_dump($r->statusCode);
        $r->close();

        $r = (yield Async::post($ip, 80, "/",
            ["Connection" => "close"],
            ["cookieK" => "cookieV"],
            "body", 2000));
        var_dump($r->statusCode);
    };

    $task1 = function() {
        $ip = (yield Async::dns("www.baidu.com"));

        $r = (yield Async::request($ip, 80, "PUT", "/",
            ["Connection" => "close"]));
        var_dump($r->statusCode);
    };

    yield $task();
    yield $task1();
    yield Async::sleep(1000);

    echo "DONE\n";

}, function($r, $e) {
    // var_dump($r);
    if ($e) {
        var_dump($e);
    }
    swoole_event_exit();
});