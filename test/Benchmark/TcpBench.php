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
        // echo $recv, "\n\n\n";
        return true;
    }

    /**
     * Test Config
     * @return Config
     */
    public function config()
    {
        return new Config("10.9.143.96", 9001);
    }
}

// 配合与server通信分包规则
$setting = [
    'open_length_check' => 1,
    'package_length_type' => 'N',
    'package_length_offset' => 0,
    'package_body_offset' => 4,
];
Benchmark::start(new TcpBench(), $setting);