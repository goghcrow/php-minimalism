<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/1/31
 * Time: 下午1:44
 */

namespace Minimalism\Test\AsyncTask;

use Minimalism\Async\AsyncDns;
use Minimalism\Async\AsyncSleep;
use Minimalism\Async\Core\AsyncTask;

require __DIR__ . "/../../vendor/autoload.php";



$t = function() {
    yield new AsyncSleep(1000);
    $ip = (yield new AsyncDns("www.baidu.com", 100));
    var_dump($ip);
    yield "hello";
};


$task = new AsyncTask($t());
$task->start(function($r, $e) {
    echo "r: $r\n";
    if ($e instanceof \Exception) {
        echo $e->getMessage(), "\n";
    }
});