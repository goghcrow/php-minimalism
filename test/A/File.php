<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/7
 * Time: 上午1:21
 */

namespace Minimalism\Test\A;


use function Minimalism\A\Client\async_read;
use function Minimalism\A\Client\async_write;
use function Minimalism\A\Core\async;

require __DIR__ . "/../../vendor/autoload.php";

function randfile($file, $count, $bs = 1024 * 1024 /*m*/)
{
    `dd if=/dev/urandom of=$file bs=$bs count=$count >/dev/null 2>&1`;
    return $count * $bs;
}

async(function() {
    $f = "tmp";
    $size = randfile($f, rand(2, 9), 1024 * 1023);

    $txt = (yield async_read($f));
    assert($size === strlen($txt));
    @unlink($f);
});


async(function() {
    $f = "tmp1";
    $f_copy = "{$f}_copy";

    $size = randfile($f, rand(2, 9), 1024 * 1023);

    $txt = (yield async_read($f));
    $writeSize = (yield async_write($f_copy, $txt));

    `diff $f $f_copy`;
    assert($size === $writeSize);

    @unlink($f);
    @unlink($f_copy);
});