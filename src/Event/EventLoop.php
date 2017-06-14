<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/6/13
 * Time: 下午1:10
 */

namespace Minimalism\Event;


class EventLoop
{
    private $nextTick, $nextTick_;
    private $timer, $timer_;
    private $onReadFd, $onReadCb, $onWriteFd, $onWriteCb;
    private $run;

    public function __construct()
    {
        $this->onReadFd     = $this->onWriteFd = [];
        $this->onReadCb     = $this->onWriteCb = [];
        $this->nextTick     = new \SplQueue();
        $this->nextTick_    = new \SplQueue();
        $this->timer        = new \SplPriorityQueue();
        $this->timer_       = [];

        register_shutdown_function(function() {
            $this->loop();
        });
    }

    /**
     * 添加|清除读事件
     * @param resource $s stream|fd
     * @param callable|null $on
     */
    public function onRead($s, callable $on = null)
    {
        // 已经close: resource(n) of type (Unknown)
        if ($on && is_resource($s)) {
            $this->onReadFd[(int)$s] = $s;
            $this->onReadCb[(int)$s] = $on;
        } else {
            unset($this->onReadFd[(int)$s]);
            unset($this->onReadCb[(int)$s]);
        }
    }

    /**
     * 添加|清除写事件
     * @param resource $s stream|fd
     * @param callable|null $on
     */
    public function onWrite($s, callable $on = null)
    {
        if ($on && is_resource($s)) {
            $this->onWriteFd[(int)$s] = $s;
            $this->onWriteCb[(int)$s] = $on;
        } else {
            unset($this->onWriteFd[(int)$s]);
            unset($this->onWriteCb[(int)$s]);
        }
    }

    /**
     * 添加nextTick事件
     * @param callable $tick
     */
    public function nextTick(callable $tick)
    {
        $this->nextTick->push($tick);
    }

    /**
     * 添加 after 定时器
     * @param int $ms
     * @param callable $after
     * @return string id
     */
    public function after($ms, callable $after)
    {
        $timer = _Timer::after($ms, $after);
        $this->timer->insert($timer, -$ms);
        $this->timer_[$timer->id] = $timer;
        return $timer->id;
    }

    /**
     * 添加 tick 定时器
     * @param int $ms
     * @param callable $tick
     * @return string id
     */
    public function tick($ms, callable $tick)
    {
        $timer = _Timer::tick($ms, $tick);
        $this->timer->insert($timer, -$ms);
        $this->timer_[$timer->id] = $timer;
        return $timer->id;
    }

    /**
     * 清除定时器
     * @param string $id
     */
    public function clear($id)
    {
        if (isset($this->timer_[$id])) {
            /** @var _Timer $timer */
            $timer = $this->timer_[$id];
            $timer->valid = false;
        }
    }

    /**
     * 停止事件循环
     */
    public function stop()
    {
        $this->run = false;
    }

    /**
     * 开启事件循环, 默认自动开启
     */
    private function loop()
    {
        if ($this->run) {
            return;
        }

        $this->run = true;

        while ($this->run) {
            $now = intval(microtime(true) * 1000);

            // 1. call next tick
            // 类似双缓冲方案
            // 防止nextTick回调嵌套调用nextTick, while死循环, 阻塞后续代码执行
            // 遍历nextTick 回调新添加的nextTick在新的空队列, swap对象, 不需要每次new新的队列
            $_ = $this->nextTick_;
            $this->nextTick_ = $this->nextTick;
            $this->nextTick = $_;
            while ($this->nextTick_->isEmpty() === false) {
                $tick = $this->nextTick_->pop();
                $tick($this);
            }

            // 2. call timer
            /** @var _Timer $timer */
            foreach (clone $this->timer as $timer) {
                if ($timer->valid === false) {
                    continue;
                }

                if ($timer->at - $now > 0) {
                    break;
                }

                $on = $timer->on;
                $on($this, $timer->id);
                if ($timer->isTick) {
                    $timer->at = $timer->ms + $now;
                } else {
                    $timer->valid = false;
                }
            }

            // 3. calc select timeout
            $timeout = null;
            if ($this->run === false) {
                $timeout = 0;
            } else if ($this->nextTick->isEmpty() === false) {
                $timeout = 0;
            } else {
                while ($this->timer->isEmpty() === false) {
                    /** @var _Timer $timer */
                    $timer = $this->timer->top();
                    // clear invalid timer
                    if ($timer->valid === false) {
                        $this->timer->extract();
                        unset($this->timer_[$timer->id]);
                        continue;
                    } else {
                        $timeout = max(0, $timer->at - $now);
                        break;
                    }
                }

                if ($timeout === null &&
                    empty($this->onReadFd) &&
                    empty($this->onWriteFd)) {
                    break; // exit loop
                }
            }

            // 4. select
            foreach ($this->onReadFd as $i => $s) {
                // 已经close: resource(n) of type (Unknown)
                if (is_resource($s) === false) {
                    $onRead = $this->onReadCb[$i];
                    $onRead($this, ""); // close
                    $this->onRead($s, null); // clear read
                    $this->onWrite($s, null); // clear write
                }
            }
            if ($this->onReadFd || $this->onWriteFd) {
                $read = $this->onReadFd;
                $write = $this->onWriteFd;
                $except = null;

                $n = stream_select($read, $write, $except, $timeout === null ? null : 0, $timeout);
                if ($n === false || $n === 0) {
                    continue;
                } else {
                    foreach ($read as $s) {
                        if (isset($this->onReadFd[(int)$s])) {
                            $onRead = $this->onReadCb[(int)$s];
                            $onRead($this, $s);
                        }
                    }
                    foreach ($write as $s) {
                        if (isset($this->onWriteFd[(int)$s])) {
                            $onWrite = $this->onWriteCb[(int)$s];
                            $onWrite($this, $s);
                        }
                    }
                }
            } else {
                if ($timeout) {
                    usleep($timeout * 1000);
                }
            }
        }
    }
}


/**
 * Class Timer
 * @package Minimalism\Event
 * @internal
 */
class _Timer
{
    public $id, $ms, $at, $on, $isTick = false, $valid = true;

    public static function after($ms, callable $after)
    {
        $timer = new static();
        $timer->ms = $ms;
        $timer->at = max(0, $ms) + intval(microtime(true) * 1000);
        $timer->on = $after;
        $timer->id = spl_object_hash($timer);
        return $timer;
    }

    public static function tick($ms, callable $tick)
    {
        $timer = new static();
        $timer->ms = $ms;
        $timer->at = max(0, $ms) + intval(microtime(true) * 1000);
        $timer->on = $tick;
        $timer->isTick = true;
        $timer->id = spl_object_hash($timer);
        return $timer;
    }
}