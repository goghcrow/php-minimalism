<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/1/31
 * Time: 下午1:40
 */

namespace Minimalism\Async;


use Minimalism\Async\Core\IAsync;

class AsyncSleep implements IAsync
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