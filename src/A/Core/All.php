<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/18
 * Time: 下午3:57
 */

namespace Minimalism\A\Core;


/**
 * Class All
 * parallel tasks
 * @package Minimalism\A\Core
 *
 * 等待所有完成（或第一个失败）TODO 修改行为
 * 收集异常, 抛出AggregateException
 */
class All implements Async
{
    public $parent;
    public $tasks;
    public $continuation;

    public $n;
    public $results;
    public $done;

    /**
     * AllTasks constructor.
     * @param \Generator[] $tasks
     * @param Task $parent
     */
    public function __construct(array $tasks, Task $parent = null)
    {
        $this->tasks = $tasks;
        $this->parent = $parent;
        $this->n = count($tasks);
        assert($this->n > 0);
        $this->results = [];
    }

    /**
     * @param callable $continuation
     * @return void
     */
    public function start(callable $continuation = null)
    {
        $this->continuation = $continuation;
        foreach ($this->tasks as $id => $task) {
            (new Task($task, $this->parent))->start($this->continuation($id));
        };
    }

    private function continuation($id)
    {
        return function($r, $ex = null) use($id) {
            if ($this->done) {
                return;
            }

            if ($ex) {
                $this->done = true;
                $k = $this->continuation;
                $k(null, $ex);
                return;
            }

            $this->results[$id] = $r;
            if (--$this->n === 0) {
                $this->done = true;
                if ($this->continuation) {
                    $k = $this->continuation;
                    $k($this->results);
                }
            }
        };
    }
}