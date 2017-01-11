<?php
namespace Minimalism;

/**
 * Class TimingWheel
 * 用swoole实现的单表盘的简易时间轮, 用来处理超时
 */
final class TimingWheel
{
    private $counter;       // timer计数器
    private $timerId;       // swoole定时器id

    private $wheelCount;    // wheel内slot数量
    private $interval;      // 间隔时间,决定了定时器最小精度
    private $maxInterval;   // wheel的最大时间范围 $interval * ($wheelCount - 1)

    private $wheel;
    private $cursor;        // wheel游标
    private $keySlot;       // key落在wheel的哪个slot中

    /**
     * TimeWheel constructor.
     * @param int $wheelCount wheel内slot数量
     * @param int $interval ms tick_interval
     *
     * $wheelCount * $interval 决定了该实例可以处理最大超时时间
     * $interval决定该实例的精确度
     */
    public function __construct($wheelCount, $interval = 1000) {
        $this->wheelCount = $wheelCount + 1;
        $this->interval = $interval;
        $this->maxInterval = $interval * $wheelCount;

        $this->init();
    }

    private function init() {
        $this->counter = 0;
        $this->cursor = 0;
        $this->timerId = 0;
        $this->keySlot = [];
        $this->wheel = array_fill(0, $this->wheelCount, []);
    }

    private function start() {
        if ($this->timerId) {
            return false;
        }
        $this->timerId  = \swoole_timer_tick($this->interval, $this->tick());
        return boolval($this->timerId);
    }

    private function tick() {
        return function($timerId) {
            if (empty($this->keySlot)) {
                $this->stop();
            }

            $this->cursor = (++$this->cursor) % $this->wheelCount;

            foreach ($this->wheel[$this->cursor] as $key => $callback) {
                try {
                    $callback($this, $key);
                } catch (\Exception $ex) {
                    debug_print_backtrace();
                    echo $ex;
                } finally {
                    unset($this->keySlot[$key]);
                }
            }
            $this->wheel[$this->cursor] = [];
        };
    }

    private function genKey() {
        if ($this->counter === PHP_INT_MAX) {
            $this->counter = 0;
        }
        
        do {
            $this->counter++;
        } while(isset($this->keySlot[$this->counter]));
        return $this->counter;
    }

    public function after($ms, $callback, $key = false) {
        if ($key === false) {
            $key = $this->genKey();
        }

        if (isset($this->keySlot[$key])) {
            throw new TimingWheelException("key $key has been added to timewheel");
        }

        $ms = max(min($this->maxInterval, $ms), $this->interval);   // interval 范围约束
        $n = ceil($ms / $this->interval);                           // 精度可能损失
        $slot = ($this->cursor + $n) % $this->wheelCount;

        $this->wheel[$slot][$key] = $callback;
        $this->keySlot[$key] = $slot;

        if ($this->timerId === 0) {
            $this->start();
        }

        return $key;
    }

    public function cancel($key) {
        if (!isset($this->keySlot[$key])) {
            return false;
        }

        $slot = $this->keySlot[$key];
        unset($this->wheel[$slot][$key]);
        unset($this->keySlot[$key]);
        return true;
    }

    public function stop() {
        $ok = \swoole_timer_clear($this->timerId);
        if ($ok) {
            $this->init();
        }
        return $ok;
    }
}

class TimingWheelException extends \RuntimeException {}