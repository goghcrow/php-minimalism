<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/1/31
 * Time: 下午2:42
 */

namespace Minimalism\Test\A;


use Minimalism\A\Client\AsyncDns;
use Minimalism\A\Client\AsyncHttpClient;
use Minimalism\A\Core\AsyncTask;
use Minimalism\A\Core\Exception\AsyncTimeoutException;

require __DIR__ . "/../../vendor/autoload.php";


function simpleGet()
{
    $t = function() {
        $ip = (yield new AsyncDns("www.baidu.com", 100));
        $cli = new AsyncHttpClient($ip, 80);
        $swCli = (yield $cli->get("/", 1000));
        assert($swCli->statusCode === 200);
    };

    $task = new AsyncTask($t());
    $task->start(function($r, $e) {
        if ($e) {
            assert(false);
        }
    });
}

simpleGet();





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
        assert($swCli->statusCode === 302);
    };

    $task = new AsyncTask($buildTask());
    $task->start(function($r, $e) {
        if ($e) {
            assert(false);
        }
    });
}

buildReq();



function seqReq()
{
    $t = function() {
        $ip = (yield new AsyncDns("www.baidu.com", 100));
        $cli = new AsyncHttpClient($ip, 80);

        $swCli = (yield $cli->setMethod("POST")
            ->setUri("/")
            ->setHeaders(["Connection" => "keep-alive", "Accept-Encoding" => ""])
            ->setData("body")
            ->setTimeout(1000));

        assert($swCli->statusCode === 302);

        $swCli = (yield $cli->get("/", 1000));
        assert($swCli->statusCode === 200);
    };

    $task = new AsyncTask($t());
    $task->start(function($r, $e) {
        if ($e) {
            assert(false);
            echo $e;
        }
    });
}

seqReq();




function testTimeout1()
{
    $t = function() {
        $ip = (yield new AsyncDns("www.baidu.com", 100));
        $cli = new AsyncHttpClient($ip, 80);

        $swCli = (yield $cli->get("/", 1));
        assert(false);
    };

    $task = new AsyncTask($t());
    $task->start(function($r, $e) {
        assert($e instanceof \Exception);
    });
}

testTimeout1();


// 异常可捕获也可以不捕获
// 最终会被收集到complete回调
function testTimeout2()
{
    $t = function() {
        $ip = (yield new AsyncDns("www.baidu.com", 100));
        $cli = new AsyncHttpClient($ip, 80);

        $e = null;
        try {
            $swCli = (yield $cli->get("/", 1));
        } catch (AsyncTimeoutException $e) {

        }
        assert($e instanceof AsyncTimeoutException);
    };

    $task = new AsyncTask($t());
    $task->start();
}

testTimeout2();


swoole_timer_after(3000, function () { swoole_event_exit(); });

function batchTest()
{
    function serial()
    {
        async(function() {
            try {
                $ip = (yield \Minimalism\A\Client\async_dns_lookup("www.baidu.com"));
                $r = (yield (new AsyncHttpClient($ip, 80))
                    ->setMethod("GET")
                    ->setUri("/")
                    ->setTimeout(3000));
                // var_dump($r->body);
                echo memory_get_usage(), "\n";
            } catch (\Exception $ex) {
                echo $ex;
            } finally {
                serial();
            }
        });
    }


    for ($i = 0; $i < 50; $i++) {
        serial();
    }
}


// batchTest();