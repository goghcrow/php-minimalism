<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/7
 * Time: 上午1:21
 */

namespace Minimalism\Test\AsyncTask;

use Minimalism\Async\Async;
use Minimalism\Async\AsyncFile;

require __DIR__ . "/../../vendor/autoload.php";

function randfile($file, $count, $bs = 1024 * 1024 /*m*/)
{
    `dd if=/dev/urandom of=$file bs=$bs count=$count >/dev/null 2>&1`;
    return $count * $bs;
}

Async::exec(function() {
    $f = "tmp";
    $size = randfile($f, rand(2, 9), 1024 * 1023);

    $txt = (yield Async::read($f));
    assert($size === strlen($txt));
    @unlink($f);
});


Async::exec(function() {
    $f = "tmp1";
    $f_copy = "{$f}_copy";

    $size = randfile($f, rand(2, 9), 1024 * 1023);

    $txt = (yield Async::read($f));
    $writeSize = (yield Async::write($f_copy, $txt));

    `diff $f $f_copy`;
    assert($size === $writeSize);

    @unlink($f);
    @unlink($f_copy);
});