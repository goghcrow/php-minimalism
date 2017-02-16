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
        // ...
        return $req;
    }

    /**
     * Test Config
     * @return Config
     */
    public function config()
    {
        $conf = new Config("115.239.210.27", 80, 200, null);
        $conf->setLabel("http-bench");
        $conf->setConnTimeout(null);
        $conf->setRecvTimeout(null);

        return $conf;

    }

    public function assert($client, $recv)
    {
        // echo $recv, "\n";
        return parent::assert($client, $recv);
    }
}

Benchmark::start(new HttpBench());