<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/17
 * Time: 上午12:48
 */

namespace Minimalism\Benchmark;


abstract class TcpTestPlan implements TestPlan
{
    public static function packFix($k)
    {
        $payload = str_repeat("o", 1024 * $k - 12);
        $payload = "hello{$payload} world!";
        $len = pack("N", strlen($payload));
        return $len . $payload;
    }

    /**
     * Payload Factory
     * @param \swoole_client $client
     * @return string
     */
    abstract public function payload($client);

    /**
     * Receive Assert
     * @param \swoole_client $client
     * @param mixed $recv
     * @return bool
     */
    abstract function assert($client, $recv);
}