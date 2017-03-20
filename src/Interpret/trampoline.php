<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/3/19
 * Time: 下午2:25
 */



class Trampoline
{
    public $fn;

    public function __construct(callable $fn)
    {
        $this->fn = $fn;
    }

    public function __invoke()
    {
        $fn = $this->fn;
        return $fn();
    }
}


function trampoline(callable $f)
{
    $fn = $f;
    while ($fn instanceof Trampoline) {
        $fn = $fn();
    }
    return $fn;
}



function sum1($n, $acc = 0)
{
    if ($n === 0) {
        return $acc;
    } else {
        return sum1($n - 1, $acc + $n);
    }
}


function sum($n, $acc = 0)
{
    if ($n === 0) {
        return $acc;
    } else {
        return new Trampoline(function() use($n, $acc) {
            return sum($n - 1, $acc + $n);
        });
    }
}

$sum = trampoline(sum(10000));
var_dump($sum);