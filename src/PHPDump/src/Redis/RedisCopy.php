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

    public function __invoke(RedisPDU $msg)
    {
        if ($msg->isRequest()) {
            $args = $msg->getArgs();
            $cmd = implode(" ", $args);
            swoole_async_write($this->file, $cmd . "\n", -1);
        }
    }
}