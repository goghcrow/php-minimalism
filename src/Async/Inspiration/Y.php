<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/12
 * Time: 下午4:50
 */

/**
 * @param callable $f
 * @return Closure
 */
function Y(callable $f)
{
    $u = function($x) use($f) {
        return $f(function($y) use($x) {
            $c = $x($x);
            return $c($y);
        });
    };

    return $u($u);
}




echo call_user_func(Y(function($fact) {
    return function($n) use($fact) {
        return $n <= 1 ? 1 : $n * $fact($n - 1);
    };
}), 5);