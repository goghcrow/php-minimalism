<?php

namespace Minimalism\Test\Validation;

use Zan\Framework\Foundation\Coroutine\Task;
use Zan\Framework\Utilities\File\File;
use Zan\Framework\Utilities\File\OnceFile;

require __DIR__ . "/../../src/Coroutine/Scheduler.php";
require __DIR__ . "/../../src/Coroutine/Task.php";
require __DIR__ . "/../../src/Coroutine/Context.php";
require __DIR__ . "/../../src/Coroutine/Event.php";
require __DIR__ . "/../../src/Coroutine/EventChain.php";
require __DIR__ . "/../../src/Coroutine/Signal.php";
require __DIR__ . "/../../src/Coroutine/Async.php";
require __DIR__ . "/File.php";
require __DIR__ . "/OnceFile.php";

function after() {
    swoole_timer_after(1, function() {
        echo "1\n";
        after();
    });
}

after();

exit;

// !! linux 的bs不支持m作为单位 !!!
function randfile($file, $count, $bs = 1024 * 1024 /*m*/)
{
    `dd if=/dev/urandom of=$file bs=$bs count=$count >/dev/null 2>&1`;
    return $count * $bs;
}


$onceWriteTest = function() {
    $f = "tmp1";
    $f_copy = "{$f}_copy";

    $size = randfile($f, rand(2, 9), 1024 * 1023);

//    var_dump($size);
//    var_dump(filesize($f));
    $c = file_get_contents($f);

    $of = new OnceFile();
    $len = (yield $of->putContents($f_copy, $c));
//    var_dump($len);

    `diff $f $f_copy`;
    @unlink($f); @unlink($f_copy);
    assert($size === $len);
};
 Task::execute($onceWriteTest());




$onceReadTest = function() {
    $f = "tmp";

    $size = randfile($f, rand(2, 9), 1024 * 1023);
//    var_dump($size);

    register_shutdown_function(function() use($f) { @unlink($f); });

    $of = new OnceFile();
    $txt = (yield $of->getContents($f));
//    var_dump(strlen($txt));

    assert($size === strlen($txt));
};
 Task::execute($onceReadTest());


$readWriteTest = function() {
    $f = "tmp3";
    $f1 = "{$f}_1";
    $f2 = "{$f}_2";
    randfile($f1, rand(2, 9), 1024 * 1023);
    randfile($f2, rand(2, 9), 1024 * 1023);
    register_shutdown_function(function() use($f,$f1,$f2) { @unlink($f);@unlink($f1);@unlink($f2);});


    $file = new File($f);

    $txt1 = file_get_contents($f1);
    $len = (yield $file->write($txt1));
    assert($len === strlen($txt1));

    $txt2 = file_get_contents($f2);
    $len = (yield $file->write($txt2));
    assert($len === strlen($txt2));

    $file->seek(0);

    $txt = (yield $file->read(-1));
    assert($txt === "$txt1$txt2");
    assert($file->eof() === false);

    $txt = (yield $file->read());
    assert($txt === "");
    assert($file->eof() === true);

    $file->seek(0);
    $len = 1024 * 1024 + 1;
    $txt1 = (yield $file->read($len));
    assert(strlen($txt1) === $len);
    assert($file->tell() === $len);

    $txt2 = (yield $file->read($len));
    assert(strlen($txt2) === $len);
    assert($file->tell() === $len * 2);

    $file->seek(0);
};
Task::execute($readWriteTest());
swoole_event_wait();



//$test = function() {
//    $f = "tmp3";
//    register_shutdown_function(function() use($f) { @unlink($f);});
//
//
//    $file = new File($f);
//
//    $txt1 = "hello";
//    $len = (yield $file->write($txt1));
//    assert($len === strlen($txt1));
//
//    $txt2 = "world";
//    $len = (yield $file->write($txt2));
//    assert($len === strlen($txt2));
//
//    $file->seek(0);
//
//    $txt = (yield $file->read(-1));
//    assert($txt === "$txt1$txt2");
//
//    var_dump($file->eof());
//
//    $txt = (yield $file->read(1));
//
//    var_dump($txt);
//    var_dump($file->eof());
//
//
////    $file->seek(0);
////    $len = 1024 * 1024 + 1;
////    $txt1 = (yield $file->read($len));
////    assert(strlen($txt1) === $len);
////    assert($file->tell() === $len);
////
////    $txt2 = (yield $file->read($len));
////    assert(strlen($txt2) === $len);
////    assert($file->tell() === $len * 2);
////
////    $file->seek(0);
//};
//Task::execute($test());
//swoole_event_wait();



//function emptyTask() {
//    if (false) {
//        yield 1;
//    }
//}


//$g = emptyTask();
//try {
//    var_dump($g->throw(new \Exception()));
//} catch (\Exception $e) {
//    var_dump($e);
//}
//exit;

//for(
//    $cur = $g->current();
//    $g->valid();
//    $cur = $g->send(null)) {
//    var_dump($cur);
//}
//exit;


/*
function t() {
    yield 1;
    yield 2;
}

$g = t();

for(
    $cur = $g->current(); $g->valid(); $cur = $g->send(null)) {
    var_dump($cur);
}

$g = t();

$cur = $g->current();
if ($g->valid()) { var_dump($cur); }

$cur = $g->send(1);
if ($g->valid()) { var_dump($cur); }
*/

//exit;
//$t = function() {
//  $r = (yield);
//    var_dump($r);
//};
//
//$g = $t();
//$g->current();
//$g->send("hello");
//exit;
//
//$tt = function() {
//    $task = function () {
//        yield 1;
//        echo 1;
//
//        $nestedTask = function () {
//            yield 2;
//            echo 2;
//        };
//        yield $nestedTask();
//    };
//
//    $ret = (yield $task());
//    var_dump($ret);
//};
//
//
//Task::execute($tt());