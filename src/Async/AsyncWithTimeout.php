<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/1/31
 * Time: 下午1:39
 */

namespace Minimalism\Async;


use Minimalism\Async\Core\IAsync;

abstract class AsyncWithTimeout implements IAsync
{
    public $complete;
    public $timeout = 1000;

    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
        return $this;
    }

    public function start(callable $complete)
    {
        $this->complete = $this->once($complete);

        swoole_timer_after($this->timeout, function() {
            $this->throwEx(new AsyncTimeoutException());
        });

        $this->execute();
    }

    abstract protected function execute();

    protected function returnVal($r)
    {
        $cb = $this->complete;
        $cb($r, null);
    }

    protected function throwEx(\Exception $ex)
    {
        $cb = $this->complete;
        $cb(null, $ex);
    }

    private function once(callable $fun)
    {
        return function(...$args) use($fun) {
            static $once = false;
            if ($once) {
                return null;
            }
            $once = true;
            return $fun(...$args);
        };
    }
}