<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/16
 * Time: 上午2:30
 */

namespace Minimalism\A\Core;


/**
 * Class CallCC
 * @package Minimalism\A\Core
 *
 * 用来做cps变换, 实质上是将
 * asyncXXX(...args, callback) : void => syncXXX(...args) Async
 * Async begin(callback) : void
 *
 * asyncInvoke :: (a, b -> void) -> void
 * syncInvoke :: a -> (b -> void)
 *
 * asyncInvoke(...args, (fn(T) : void)) : void
 * syncInvoke(...args) : (fn(T): void)
 *
 */
class CallCC implements Async
{
    public $fun;

    public function __construct(callable $fun)
    {
        $this->fun = $fun;
    }

    public function begin(callable $continuation)
    {
         $fun = $this->fun;
         // 不处理返回值，user-func返回值通过延续进行传递
         $fun($continuation);
    }
}