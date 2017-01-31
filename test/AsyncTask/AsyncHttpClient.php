<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/1/31
 * Time: 下午2:42
 */

namespace Minimalism\Test\AsyncTask;

use Minimalism\AsyncTask\AsyncDns;
use Minimalism\AsyncTask\AsyncHttpClient;
use Minimalism\AsyncTask\Core\AsyncTask;
use Minimalism\AsyncTask\AsyncTimeoutException;

require __DIR__ . "/../../vendor/autoload.php";


function simpleGet()
{
    $t = function() {
        $ip = (yield new AsyncDns("www.baidu.com", 100));
        $cli = new AsyncHttpClient($ip, 80);

        $swCli = (yield $cli->get("/", 1000));
        var_dump($swCli->statusCode);
    };

    $task = new AsyncTask($t());
    $task->start(function($r, $e) {
        // var_dump($r);
        if ($e instanceof \Exception) {
            echo $e->getMessage(), "\n";
        }
    });
}

//simpleGet();






function buildReq()
{
    $buildTask = function() {
        $ip = (yield new AsyncDns("www.baidu.com", 100));
        $cli = new AsyncHttpClient($ip, 80);
        $swCli = (yield $cli->setMethod("POST")
            ->setUri("/")
            ->setHeaders(["hk" => "hv", "Accept-Encoding" => ""])
            ->setCookies(["cookieKey" => "cookeVal"])
            ->setData("body")
            ->setTimeout(1000));
        var_dump($swCli->statusCode);
    };

    $task = new AsyncTask($buildTask());
    $task->start(function($r, $e) {
        if ($e instanceof \Exception) {
            echo $e->getMessage(), "\n";
        }
    });
}

//buildReq();




function seqReq()
{
    // TODO bug 连续两次请求
    // WARNING	http_client_parser_on_message_complete: http_response_uncompress failed.
    $t = function() {
        $ip = (yield new AsyncDns("www.baidu.com", 100));
        $cli = new AsyncHttpClient($ip, 80);

        $swCli = (yield $cli->get("/", 1000));
        var_dump($swCli->statusCode);

        $swCli = (yield $cli->setMethod("POST")
            ->setUri("/")
            ->setHeaders(["hk" => "hv", "Accept-Encoding" => ""])
            ->setData("body")
            ->setTimeout(1000));

        var_dump($swCli->statusCode);
    };

    $task = new AsyncTask($t());
    $task->start(function($r, $e) {
        // var_dump($r);
        if ($e instanceof \Exception) {
            echo $e->getMessage(), "\n";
        }
    });
}

// seqReq();




function loopGet()
{
    $t = function() {
        $ip = (yield new AsyncDns("www.baidu.com", 100));
        $cli = new AsyncHttpClient($ip, 80);

        while(true) {
            try {
                $swCli = (yield $cli->get("/", 1000));
                var_dump($swCli->statusCode);
            } catch (AsyncTimeoutException $e) {
                // 旧连接超时，新建连接
                $cli->close();
                $cli = new AsyncHttpClient($ip, 80);
            }
        }
    };

    $task = new AsyncTask($t());
    $task->start(function($r, $e) {
        // var_dump($r);
        if ($e instanceof \Exception) {
            echo $e, "\n";
        }
    });
}

//loopGet();


function testTimeout1()
{
    $t = function() {
        $ip = (yield new AsyncDns("www.baidu.com", 100));
        $cli = new AsyncHttpClient($ip, 80);

        $swCli = (yield $cli->get("/", 1));
        var_dump($swCli->statusCode);
    };

    $task = new AsyncTask($t());
    $task->start(function($r, $e) {
        // var_dump($r);
        if ($e instanceof \Exception) {
            echo $e, "\n";
        }
    });
}

//testTimeout1();


// 异常可捕获也可以不捕获
// 最终会被收集到complete回调
function testTimeout2()
{
    $t = function() {
        $ip = (yield new AsyncDns("www.baidu.com", 100));
        $cli = new AsyncHttpClient($ip, 80);

        try {
            $swCli = (yield $cli->get("/", 1));
            var_dump($swCli->statusCode);
        } catch (AsyncTimeoutException $e) {
            echo $e, "\n";
        }
    };

    $task = new AsyncTask($t());
    $task->start(function($r, $e) {
        // var_dump($r);
        if ($e instanceof \Exception) {
            echo $e, "\n";
        }
    });
}

//testTimeout2();