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
use Minimalism\Benchmark\TcpTestPlan;

require __DIR__ . "/../../vendor/autoload.php";

class TcpBench extends TcpTestPlan
{

    /**
     * Payload Factory
     * @param \swoole_client $client
     * @return string
     */
    public function payload($client)
    {
        static $c;
        if ($c === null) {
            $c = self::packFix(2);
        }
        return $c;
    }

    /**
     * Receive Assert
     * @param \swoole_client $client
     * @param mixed $recv
     * @return bool
     */
    function assert($client, $recv)
    {
        // echo $recv;
        return true;
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

Benchmark::start(new TcpBench());