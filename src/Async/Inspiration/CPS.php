<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/1/31
 * Time: 下午10:33
 */

namespace Minimalism\Async\Inspiration;


class CPS
{
    public static function iif(callable $test, callable $then, callable $orelse)
    {
        if ($test()) {
            return $then();
        } else {
            return $orelse();
        }
    }

    public static function iwhile()
    {

    }

    public static function ifor()
    {

    }
}



function fact_cps($n, callable $k = null)
{
    if ($k === null) {
        $k = function($v) { return $v; }; // id
    }
    if ($n === 1) {
        return $k(1);
    }
    return fact_cps($n - 1, function($v) use($n, $k) { return $k($v * $n); });
}

function fib_cps($n, callable $k = null)
{
    if ($k === null) {
        $k = function($v) { return $v; }; // id
    }
    if ($n === 0) {
        return $k(0);
    }
    if ($n === 1) {
        return $k(1);
    }
    return fib_cps($n - 1, function($v1) use($n, $k) {
       return fib_cps($n - 2, function($v2) use($n, $k, $v1) {
           return $k($v1 + $v2);
       });
    });
}

//echo fib_cps(5);
//echo fact_cps(3);
//exit;

