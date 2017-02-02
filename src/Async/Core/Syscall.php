<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/2
 * Time: 下午6:28
 */

namespace Minimalism\Async\Core;


class Syscall
{
    private $callback = null;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function __invoke(AsyncTask $task)
    {
        $cb = $this->callback;
        return $cb($task);
    }
}