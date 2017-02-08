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
    private $timer;

    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
        return $this;
    }

    public function start(callable $complete)
    {
        $this->complete = $this->once($complete);
        $this->execute();
        $this->beginTimeout();
    }

    abstract protected function execute();

    protected function returnVal($r)
    {
        $this->cancelTimeout();
        $cb = $this->complete;
        $cb($r, null);
    }

    protected function throwEx(\Exception $ex)
    {
        $this->cancelTimeout();
        $cb = $this->complete;
        $cb(null, $ex);
    }

    private function beginTimeout()
    {
        $this->timer = swoole_timer_after($this->timeout, function() {
            $this->throwEx(new AsyncTimeoutException(static::class));
        });
    }

    private function cancelTimeout()
    {
        if ($this->timer) {
            swoole_timer_clear($this->timer);
            $this->timer = null;
        }
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