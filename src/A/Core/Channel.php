<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/3/19
 * Time: 上午12:56
 */

namespace Minimalism\A\Core;


// TODO 处理超时，当超时时，从recvQ或者sendQ移除

class Channel
{
    public $recvQ;
    public $sendQ;

    public function __construct()
    {
        $this->recvQ = new \SplQueue();
        $this->sendQ = new \SplQueue();
    }

    public function send($val = null)
    {
        return callcc(function($cc) use($val) {
            if ($this->recvQ->isEmpty()) {
                $this->sendQ->enqueue([$cc, $val]);
            } else {
                $recvCc = $this->recvQ->dequeue();
                // TODO 斟酌顺序，先执行发送者代码还是先执行接收者代码
                // 发送时先执行接收者后续代码
                $recvCc($val, null);
                $cc(null, null);
            }
        });
    }

    public function recv()
    {
        return callcc(function($cc) {
            if ($this->sendQ->isEmpty()) {
                $this->recvQ->enqueue($cc);
            } else {
                list($sendCc, $val) = $this->sendQ->dequeue();
                // TODO 斟酌顺序，先执行发送者代码还是先执行接收者代码
                // 接受时先执行发送者后续代码
                $sendCc(null, null);
                $cc($val, null);
            }
        });
    }

    public function close()
    {
        // TODO
    }
}