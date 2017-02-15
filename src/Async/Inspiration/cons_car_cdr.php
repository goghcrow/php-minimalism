<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/11
 * Time: 上午12:02
 */


function cons($x, $y)
{
    return function(\Closure $f) use($x, $y) {
        return $f($x, $y);
    };
}

function car(\Closure $list)
{
    return $list(function($a, $b) { return $a; });
}

function cdr(\Closure $list)
{
    return $list(function($a, $b) { return $b; });
}


$list = cons(1, 2);
assert(car($list) === 1);
assert(cdr($list) === 2);

assert(car(cdr(cons(1, cons(2, 3)))) === 2);
