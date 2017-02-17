<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/17
 * Time: 上午2:47
 */

namespace Minimalism\Test\Benchmark;


use Minimalism\Benchmark\Benchmark;
use Minimalism\Benchmark\Config;
use Minimalism\Benchmark\NovaTestPlan;

require __DIR__ . "/../../vendor/autoload.php";

class NovaBench extends NovaTestPlan
{
    /**
     * Receive Assert
     * @param \swoole_client $client
     * @param mixed $recv
     * @return bool
     */
    public function assert($client, $recv)
    {
        // print_r($recv);
        return true;
    }

    /**
     * Test Config
     * @return Config
     */
    public function config()
    {
        return new Config("10.9.188.33", 8050);
    }
}


$service = "com.youzan.material.general.service.MediaService";
$method = "getMediaList";
$args = [
    'query' => [
        'categoryId' => 2,
        'kdtId' => 1,
        'pageNo' => 1,
        'pageSize' => 5,
    ],
];

Benchmark::start(new NovaBench($service, $method, $args));