<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/17
 * Time: 上午1:43
 */

namespace Minimalism\Benchmark;


class Config
{
    public $ip;
    public $port;
    public $connTimeout;
    public $recvTimeout;
    public $requests;
    public $procNum;
    public $concurrency;
    public $label = "test";

    public function __construct($ip, $port, $concurrency, $requests = null)
    {
        $this->ip = $ip;
        $this->port = $port;
        
        $this->procNum = swoole_cpu_num() * 2;
        // 进程均分Concurrency
        $this->concurrency = intval(ceil($concurrency / $this->procNum));
        if ($requests === null) {
            $this->requests = null;
        } else {
            // 进程内每个连接均分requests
            $this->requests = intval(ceil($requests / $this->procNum / $this->concurrency));
        }
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * @param int $connTimeout ms
     */
    public function setConnTimeout($connTimeout)
    {
        $this->connTimeout = $connTimeout;
    }

    /**
     * @param int $recvTimeout ms
     */
    public function setRecvTimeout($recvTimeout)
    {
        $this->recvTimeout = $recvTimeout;
    }

    public function __toString()
    {
        return json_encode((array) $this, JSON_PRETTY_PRINT);
    }
}