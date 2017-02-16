<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/17
 * Time: 上午12:51
 */

namespace Minimalism\Benchmark;


abstract class HttpTestPlan implements TestPlan
{
    /**
     * Payload Factory
     * @param \swoole_http_client $client
     * @return HttpRequest
     */
    abstract public function payload($client);

    /**
     * Receive Assert
     * @param \swoole_http_client $client
     * @param mixed $recv
     * @return bool
     */
    public function assert($client, $recv)
    {
        return $client->statusCode === 200;
    }
}