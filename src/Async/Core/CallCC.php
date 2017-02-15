<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/16
 * Time: 上午2:30
 */

namespace Minimalism\Async\Core;

class CallCC implements IAsync
{
    public $fun;

    public function __construct(callable $fun)
    {
        $this->fun = $fun;
    }

    public function start(callable $continuation)
    {
        $fun = $this->fun;
        $fun($continuation);
    }
}


/**
 * call/cc
 *
 * call-with-current-continuation
 *
 * @param callable $fun
 *      $fun 参数会接收到continuation $k
 *      $k的签名: void fun($result = null, \Exception = null)
 *      可以抛出异常或者以同步方式返回值
 * @return CallCC
 *
 * 可以使用call/cc在async环境中将任务异步接口转换为同步接口
 */
/*
function callcc(callable $fun)
{
    return new CallCC($fun);
}
*/


// 可以使用call/cc轻松转换很多异步接口
// yield asyncSleep
/*
function asyncSleep($ms)
{
    return new CallCC(function($k) use($ms) {
        swoole_timer_after($ms, function() use($k) {
            $k();
        });
    });
}
*/