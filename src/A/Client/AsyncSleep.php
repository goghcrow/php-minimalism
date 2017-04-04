<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/1/31
 * Time: 下午1:40
 */

namespace Minimalism\A\Client;


use Minimalism\A\Core\Async;

class AsyncSleep implements Async
{
    public $sleep;
    public $complete;

    public function __construct($sleep)
    {
        $this->sleep = $sleep;
    }

    public function start(callable $continuation)
    {
        $this->complete = $continuation;
        swoole_timer_after($this->sleep, function() {
            $cb = $this->complete;
            $cb(null, null);
        });
    }
}