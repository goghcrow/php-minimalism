<?php

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

function fac1($n, $s = 1)
{
    if ($n <= 1) {
        return $s;
    } else {
        return fac1($n - 1, $s * $n);
    }
}

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

// Function<R, T>
function trampoline(callable $f)
{
    $func = $f;
    while (is_callable($func)) {
        $func = $func();
    }
    return $func;
}

function tail_fac($n, $s)
{
    if ($n <= 1) {
        return $s;
    } else {
        return function() use($n, $s) {
            return tail_fac($n - 1, $s * $n);
        };
    }
}

function fac2($n)
{
    return trampoline(tail_fac($n, 1));
}


//echo fac1(10000000), "\n";
echo fac2(10000000), "\n";