<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/7
 * Time: 上午10:44
 */

namespace Minimalism\Test;

use Minimalism\A\Async;
use Minimalism\A\AsyncHttpClient;
use Minimalism\A\Core\AsyncTask;
use Minimalism\Autoload;

require __DIR__ . "/../src/Autoload.php";


$dirs = [
    __DIR__ . "/..",
    "http://gitlab.qima-inc.com/chuxiaofeng/_/raw/master"
];

// $path 按照dirs优先级匹配
$psr4 = [
    "Minimalism\\" => "src",
];
new Autoload($dirs, $psr4);


/**
 * @return \Generator
 */
function request()
{
    $ip = (yield Async::dns("www.baidu.com"));
    /* @var \swoole_http_client */
    $http = (yield (new AsyncHttpClient($ip, 80))
        ->setMethod("GET")
        ->setUri("/")
        ->setHeaders([])
        ->setCookies([])
        ->setData("")
        ->setTimeout(1000));

    echo memory_get_usage(), "\n";
    /** @noinspection PhpUndefinedMethodInspection */
    $http->close();
}

function loop()
{

    $task = new AsyncTask(request());
    $task->start(function($r, $e) {
        // echo "r: $r\n";
        if ($e instanceof \Exception) {
            echo $e->getMessage(), "\n";
        }
        loop();
    });
}


loop();