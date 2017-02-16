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
        // TODO: Implement assert() method.
    }

    /**
     * Test Config
     * @return Config
     */
    public function config()
    {
        // TODO: Implement config() method.
    }
}

Benchmark::start(new NovaBench());