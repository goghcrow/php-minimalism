<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/11
 * Time: 上午1:56
 */


class Thread
{
    public $tasklist;

    public function __construct()
    {
        $this->tasklist = new \SplStack();
    }

    public function sched()
    {
        if (!$this->tasklist->isEmpty()) {
            $task = $this->tasklist->pop();
            $task();
        }
    }

    public function gettask($k)
    {
        return function() use($k){
            return $k;
        };
    }

    public function iyield(callable $k)
    {
        return function() use($k) {
            $this->tasklist->push($k);
            $this->sched();
        };
    }

    public function fork($k)
    {
        $task = $this->gettask($k);
        if (is_callable($task)) {
            $this->tasklist->push($task);
            return 1; // TODO ++
        } else {
            return 0;
        }
    }

    public function thread(callable $runnable)
    {
        $runnable($this);
    }
}


$thread = new Thread();

if ($thread->fork() > 0) {
    if ($thread->fork() > 0) {
        $thread->sched();
    } else {
        thread2();
    }
} else {
    $thread->thread(function(Thread $thread) {
        return $thread->iyield(function() {

        });
    });
}




