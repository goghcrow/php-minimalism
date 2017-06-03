<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/5/31
 * Time: 下午3:21
 */

namespace Minimalism\PHPDump\Redis;


class RedisCopy
{
    public $file;

    public function __construct($file)
    {
        $this->file = $file;
    }

    public function __invoke(RedisPDU $redisMsg)
    {
        // swoole_async_write($this->file, $sql, -1);
    }
}