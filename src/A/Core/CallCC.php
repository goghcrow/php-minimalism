<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/16
 * Time: 上午2:30
 */

namespace Minimalism\A\Core;


class CallCC implements Async
{
    public $fun;

    public function __construct(callable $fun)
    {
        $this->fun = $fun;
    }

    public function start(callable $continuation)
    {
        $fun = $this->fun;

        // 不处理返回值，user-func返回值通过延续进行传递
        $fun($continuation);
    }
}