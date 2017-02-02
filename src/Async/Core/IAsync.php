<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/1/31
 * Time: 下午1:41
 */

namespace Minimalism\Async\Core;

/**
 * Interface IAsync
 * @package Minimalism\Async\Core
 */
interface IAsync
{
    /**
     * 开启异步任务，立即返回，任务完成回调complete
     * @param callable $complete
     * @return void
     */
    public function start(callable $complete);
}