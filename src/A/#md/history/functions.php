<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/3/5
 * Time: 下午6:43
 */


/**
 * @param \Generator|callable|mixed $task
 * @param callable|null $continuation
 * @param AsyncTask $parent
 * @param array $ctx Context
 * @param array $args
 */
/*
function async($task, callable $continuation = null, AsyncTask $parent = null, array $ctx = [], ...$args)
{
    if (is_callable($task)) {
        try {
            $task = $task(...$args);
        } catch (\Exception $ex) {
            $continuation(null, $ex);
            return;
        }
    }

    if ($task instanceof \Generator) {
        foreach ($ctx as $k => $v) {
            $this->generator->$k = $v;
        }
        (new AsyncTask($task, $parent))->start($continuation, $ctx);
    } else {
        $continuation($task, null);
    }
}
*/

/**
 * @internal
 * @param $k
 * @param $n
 * @return \Closure
 */
//function _kargn($k, $n = -1)
//{
//    return function() use($n, $k) {
//        if ($n === -1) {
//            return $k();
//        } else {
//            return $k(func_get_arg($n));
//        }
//    };
//}