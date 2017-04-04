<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/3/18
 * Time: 上午11:48
 */

namespace Minimalism\A\Core;


class BufferChannel
{
    public $cap;
    public $queue;
    public $recvCc;
    public $sendCc;

    public function __construct($cap)
    {
        assert($cap > 0);
        $this->cap = $cap;
        $this->queue = new \SplQueue();
        $this->sendCc = new \SplQueue();
        $this->recvCc = new \SplQueue();
    }

    public function recv()
    {
        return callcc(function($cc) {
            if ($this->queue->isEmpty()) {
                // 当无数据可接收时, 阻塞住
                $this->recvCc->enqueue($cc); // 让出控制流
            } else {
                // 当有数据可接收时, 先接收数据
                $val = $this->queue->dequeue();
                $this->cap++;
                $cc($val, null);
            }

            $this->recvPingPong();
            // defer([$this, "recvPingPong"]);
        });
    }

    public function send($val = null)
    {
        return callcc(function($cc) use($val) {
            if ($this->cap > 0) {
                // 当缓存未满，发送数据直接加入缓存
                $this->queue->enqueue($val);
                $this->cap--;
                $cc(null, null);
            } else {
                // 当缓存满，阻塞发送者
                $this->sendCc->enqueue([$cc, $val]); // 让出控制流
            }

            $this->sendPingPong();
            // 防止多个发送者时，数据全部来自某个发送者
            // 但是defer之后, 事件循环可能会停, 然后没有然后了
            // 除非把defer 实现为swoole_timer_after(1, ...)
            // defer([$this, "sendPingPong"]);
        });
    }

    public function recvPingPong()
    {
        // 当有阻塞的发送者，唤醒其发送数据
        if (!$this->sendCc->isEmpty() && $this->cap > 0) {
            list($sendCc, $val) = $this->sendCc->dequeue();
            $this->queue->enqueue($val);
            $this->cap--;
            $sendCc(null, null);

            // 当有阻塞的接收者，唤醒其接收数据
            if (!$this->recvCc->isEmpty() && !$this->queue->isEmpty()) {
                $recvCc = $this->recvCc->dequeue();
                $val = $this->queue->dequeue();
                $this->cap++;
                $recvCc($val);

                $this->recvPingPong();
            }
        }
    }

    public function sendPingPong()
    {
        // 当有阻塞的接收者，唤醒其接收数据
        if (!$this->recvCc->isEmpty() && !$this->queue->isEmpty()) {
            $recvCc = $this->recvCc->dequeue();
            $val = $this->queue->dequeue();
            $this->cap++;
            $recvCc($val);

            // 当有阻塞的发送者，唤醒其发送数据
            if (!$this->sendCc->isEmpty() && $this->cap > 0) {
                list($sendCc, $val) = $this->sendCc->dequeue();
                $this->queue->enqueue($val);
                $this->cap--;
                $sendCc(null, null);

                $this->sendPingPong();
            }
        }
    }

    public function close()
    {
        // TODO
    }
}