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

    /**
     * @var int 连接超时时间
     */
    public $connTimeout;

    /**
     * @var int 等待响应数据超时时间
     */
    public $recvTimeout;

    /**
     * @var int 总请求数, 会均分到子进程
     */
    public $requests;

    /**
     * @var int 进程数, 默认 cpu_num, 不要修改
     * @internal
     */
    public $procNum;

    /**
     * @var int 并发数, 会均分到子进程
     */
    public $concurrency;

    /**
     * @var string 测试名称
     */
    public $label = "test";

    public function __construct($ip, $port, $concurrency = 200, $requests = null)
    {
        $this->ip = $ip;
        $this->port = $port;
        $this->concurrency = $concurrency;
        $this->requests = $requests;
        
        $this->procNum = swoole_cpu_num();
        // 进程均分Concurrency
        $this->concurrency = intval(ceil($concurrency / $this->procNum));
        if ($requests === null) {
            $this->requests = null;
        } else {
            // 进程内每个连接均分requests
            $this->requests = intval(ceil($requests / $this->procNum / $this->concurrency));
        }
    }

    public function __toString()
    {
        return json_encode((array) $this, JSON_PRETTY_PRINT);
    }
}