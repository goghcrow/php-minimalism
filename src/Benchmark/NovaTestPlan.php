<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/17
 * Time: 上午12:53
 */

namespace Minimalism\Benchmark;


abstract class NovaTestPlan implements TestPlan
{
    public static $cache;

    public $service;
    public $method;
    public $args;
    public $attach;

    public function __construct($service, $method, array $args = [], array $attach = [])
    {
        $this->service = $service;
        $this->method = $method;
        $this->args = $args;
        $this->attach = $attach;
    }

    /**
     * Payload Factory
     * @param \swoole_client $client
     * @return mixed
     */
    public function payload($client)
    {
        if (self::$cache === null) {
            self::$cache =  NovaClient::packNova($client, $this->service, $this->method, $this->args, $this->attach);
        }
        return self::$cache;
    }

    /**
     * Receive Assert
     * @param \swoole_client $client
     * @param mixed $recv
     * @return bool
     */
    abstract public function assert($client, $recv);
}