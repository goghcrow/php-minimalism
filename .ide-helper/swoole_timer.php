<?php

/**
 * class swoole_timer
 *
 * @since 3.0.4
 *
 * @package swoole_timer
 */

/**
 * class swoole_timer 使用方法和 swoole_timer_tick、swoole_timer_after、swoole_timer_clear、swoole_timer_exist 相同
 *
 * @since 3.0.4
 *
 */
class swoole_timer
{

    /**
     * tick 设置一个间隔时钟定时器，与 after 定时器不同的是 tick 定时器会持续触发，直到调用 clear 清除。
     *
     * @since 3.0.4
     *
     * @param int      $ms 指定时间，单位毫秒
     * @param callable $callback 时间到期后执行的函数，必须是可调用的
     * @param mixed    $param [optional] 用户参数，该参数被传递给 $callback 中，如果有多个参数可以使用数组形式
     * ```php
     * 示例，如下代码在 worker_id=1 的 worker 进程启动时创建了一个 3 秒的间隔定时器
     * $server->on('WorkerStart', function ($serv, $worker_id){
     *     if (0 == $worker_id) {
     *         $timer_id = swoole_timer::tick(3000, function(){
     *             echo "swoole_timer::tick..." . "\n";
     *         });
     *     echo "timer_id: $timer_id" . "\n";
     *     }
     * });
     * ```
     * @return int|bool 成功返回定时器 time_id，失败返回 false
     */
    public static function tick(int $ms, callable $callback, $param = null)
    {
    }

    /**
     * 在指定的时间 $ms 后执行函数 $callback，执行完后定时器就会销毁，非阻塞。
     * @since 3.0.4
     *
     * @param int      $ms 指定时间，单位毫秒
     * @param callable $callback 回调函数
     * @param mixed    $param [optional] 用户参数，该参数被传递给 $callback 中，如果有多个参数可以使用数组形式
     *
     * @return int|bool 成功返回定时器 id，失败返回false
     */
    public static function after(int $ms, callable $callback, $param = null)
    {
    }

    /**
     * 判断 $time_id 标识的定时器是否存在
     * @since 3.0.4
     *
     * @param int $timer_id 定时器 id
     *
     * @return bool 定时器 $timer_id 存在返回 true，不存在返回 false
     */
    public static function exists($timer_id)
    {
    }

    /**
     * 清除本当前进程中 $timer_id 标识的定时器
     * @since 3.0.4
     *
     * @param int $timer_id 定时器标识
     *
     * @return bool 成功返回true，失败返回 false
     */
    public static function clear($timer_id)
    {
    }

}
