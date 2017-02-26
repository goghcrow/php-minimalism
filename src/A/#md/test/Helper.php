<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/19
 * Time: 下午11:26
 */

namespace Minimalism\A\Core\dev;


class Helper
{
    public static function once(callable $fun)
    {
        $called = false;
        $result = null;

        return function(...$args) use($fun, &$called, &$result) {
            if ($called === false) {
                $called = true;
                $result = $fun(...$args);
            }
            return $result;
        };
    }

    /**
     * @param callable[] ...$funs
     * @return array
     */
    public static function only(...$funs)
    {
        $called = false;
        $results = [];

        foreach ($funs as $i => $fun) {
            $funs[$i] = function(...$args) use($fun, &$called, &$results, $i) {
                if ($called === false) {
                    $called = true;
                    $results[$i] = $fun(...$args);
                }
                return isset($results[$i]) ? $results[$i] : null;
            };
        }
        return $funs;
    }
}



function make_counter($n = 0)
{
    return function() use(&$n) {
        return ++$n;
    };
}
$f = Helper::once(make_counter());
assert($f() === 1);
assert($f() === 1);
assert($f() === 1);


list($c0, $c10, $c100) = Helper::only(make_counter(0), make_counter(10), make_counter(100));
assert($c10() === 11);
assert($c10() === 11);
assert($c0() === null);
assert($c100() === null);
