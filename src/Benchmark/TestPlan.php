<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/16
 * Time: 下午10:22
 */

namespace Minimalism\Benchmark;

interface TestPlan
{
    /**
     * Test Config
     * @return Config
     */
    public function config();

    /**
     * Payload Factory
     * @param \swoole_client|\swoole_http_client| $client
     * @return mixed
     */
    public function payload($client);

    /**
     * Receive Assert
     * @param \swoole_client|\swoole_http_client|\swoole_mysql $client
     * @param mixed $recv
     * @return bool
     */
    public function assert($client, $recv);
}