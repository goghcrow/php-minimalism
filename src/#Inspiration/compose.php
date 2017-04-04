<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/3/26
 * Time: 下午10:28
 */

// TODO exception handler


function defer($syncFn)
{
    return function($ctx, $next) use($syncFn) {
        $syncFn($ctx, $next);
    };
}

function array_right_reduce(array $input, callable $function, $initial = null)
{
    return array_reduce(array_reverse($input, true), $function, $initial);
}


function trace($asyncFn) {
    return function($ctx, $next) use($asyncFn) {
        var_dump($ctx);
        $asyncFn($ctx, $next);
    };
}

// fn :: ($ctx, $next)
function compose1(array $asyncFns, $k)
{
    // for debug
    $asyncFns = array_map(__NAMESPACE__ . "\\trace", $asyncFns);

    return array_right_reduce($asyncFns, function($rightCarry, $leftFn) {
        return function($ctx) use($rightCarry, $leftFn) {
            $leftFn($ctx, $rightCarry);
        };
    }, $k);
}


$fn = compose1([
    function($ctx, $next) {
        swoole_timer_after(500, function() use($next, $ctx) {
            $next($ctx + 1);
        });
    },
    defer(function($ctx, $next) {
        $next($ctx + 1);
    }),
], function($ctx) {
    var_dump($ctx);
});

//$fn(1);