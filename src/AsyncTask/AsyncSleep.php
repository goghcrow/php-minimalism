<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/1/31
 * Time: 下午1:40
 */

namespace Minimalism\AsyncTask;


use Minimalism\AsyncTask\Core\Async;

class AsyncSleep implements Async
{
    public $sleep;
    public $complete;

    public function __construct($sleep)
    {
        $this->sleep = $sleep;
    }

    public function start(callable $complete)
    {
        $this->complete = $complete;
        swoole_timer_after($this->sleep, function() {
            $cb = $this->complete;
            $cb(null, null);
        });
    }
}