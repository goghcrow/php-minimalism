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
    private $fun = null;

    public function __construct(callable $fun)
    {
        $this->fun = $fun;
    }

    public function __invoke(AsyncTask $task)
    {
        $cb = $this->fun;
        return $cb($task);
    }
}