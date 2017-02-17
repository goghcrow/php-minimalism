<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/17
 * Time: 上午2:14
 */

namespace Minimalism\Test\Benchmark;

use Minimalism\Benchmark\Benchmark;
use Minimalism\Benchmark\Config;
use Minimalism\Benchmark\HttpRequest;
use Minimalism\Benchmark\HttpTestPlan;

require __DIR__ . "/../../vendor/autoload.php";

class HttpBench extends HttpTestPlan
{

    /**
     * Payload Factory
     * @param \swoole_http_client $client
     * @return HttpRequest
     */
    public function payload($client)
    {
        $req = new HttpRequest();
        $req->method = "GET";
        $req->uri = "/lookup?topic=zan_mqworker_test";
        // $req->headers = [];
        return $req;
    }

    /**
     * Test Config
     * @return Config
     */
    public function config()
    {
        $conf = new Config("10.9.6.49", 4161);
        $conf->concurrency = 200;
        $conf->requests = null;
        $conf->label = "http-bench";
        $conf->connTimeout = null;
        $conf->recvTimeout = null;

        return $conf;

    }

    /**
     * @param \swoole_http_client $client
     * @param mixed $recv
     * @return bool
     */
    public function assert($client, $recv)
    {
        // echo $client->statusCode";
        // echo $recv, "\n";
        return parent::assert($client, $recv);
    }
}

$setting = [
    "keep_alive" => true,
];
Benchmark::start(new HttpBench(), $setting);