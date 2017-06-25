<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/3/26
 * Time: 下午3:07
 */

namespace Minimalism\A\Core;


use Minimalism\A\Core\Exception\AsyncTimeoutException;

final class FutureTask
{
    const PENDING = 1;
    const DONE = 2;
    const TIMEOUT = 3;

    private $timerId;
    private $cc;

    public $state;
    public $result;
    public $ex;

    public function __construct(\Generator $gen, Task $parent = null)
    {
        $this->state = self::PENDING;

        $asyncTask = new Task($gen, $parent);

        $asyncTask->start(function($r, $ex = null)  {
            // PENDING or TIMEOUT
            if ($this->state === self::TIMEOUT) {
                return;
            }

            // PENDING -> DONE
            $this->state = self::DONE;

            if ($cc = $this->cc) {
                // 有cc, 说明有call get方法挂起协程, 在此处唤醒
                if ($this->timerId) {
                    swoole_timer_clear($this->timerId);
                }
                $cc($r, $ex);
            } else {
                // 无挂起, 暂存执行结果
                $this->result = $r;
                $this->ex = $ex;
            }
        });
    }

    /**
     * 获取异步任务结果, 当设置超时时间, 超时之后会抛出异常
     * @param int $timeout 0 means block forever
     * @return Async
     * @throws AsyncTimeoutException
     */
    public function get($timeout = 0)
    {
        return callcc(function($cc) use($timeout) {
            // PENDING or DONE
            if ($this->state === self::DONE) {
                // 获取结果时, 任务已经完成, 同步返回结果
                // 这里也可以考虑用defer实现, 异步返回结果, 首先先释放php栈, 降低内存使用
                $cc($this->result, $this->ex);
            } else {
                // 获取结果时未完成, 保存$cc, 开启定时器(如果需要), 挂起等待
                $this->cc = $cc;
                $this->getResultTimeout($timeout);
            }
        });
    }

    private function getResultTimeout($timeout)
    {
        if (!$timeout) {
            return;
        }

        $this->timerId = swoole_timer_after($timeout, function() {
            assert($this->state === self::PENDING);
            $this->state = self::TIMEOUT;
            $cc = $this->cc;
            $cc(null, new AsyncTimeoutException());
        });
    }
}