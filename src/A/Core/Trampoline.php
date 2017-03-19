<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/3/19
 * Time: 下午2:25
 */

namespace Minimalism\A\Core;


class Trampoline
{
    public $fn;

    public function __construct(callable $fn)
    {
        $this->fn = $fn;
    }

    public function __invoke()
    {
        $fn = $this->fn;
        return $fn();
    }
}