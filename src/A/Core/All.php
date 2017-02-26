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
 */
class All implements IAsync
{
    public $parent;
    public $tasks;
    public $continuation;

    public $n;
    public $results;

    /**
     * AllTasks constructor.
     * @param \Generator[] $tasks
     * @param AsyncTask $parent
     */
    public function __construct(array $tasks, AsyncTask $parent = null)
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
            (new AsyncTask($task, $this->parent))->start($this->continuation($id));
        };
    }

    public function continuation($id)
    {
        return function($r, $ex = null) use($id) {
            // 无需处理异常，会直接透传，
            // 如果需要精确控制多个任务异常，应该在每个任务内部自行捕获处理
            assert($ex === null);
            $this->results[$id] = $r;
            if (--$this->n === 0) {
                if ($this->continuation) {
                    $c = $this->continuation;
                    $c($this->results);
                }
            }
        };
    }
}