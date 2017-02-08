<?php

namespace Minimalism\functions;

// !! linux 的bs不支持m作为单位 !!!
function randfile($file, $count, $bs = 1024 * 1024 /*m*/)
{
    `dd if=/dev/urandom of=$file bs=$bs count=$count >/dev/null 2>&1`;
    return $count * $bs;
}

function randstr($length = 1024)
{
    $f = fopen("/dev/urandom", "r");
    $r = fread($f, $length);
    fclose($f);
    return $r;
}