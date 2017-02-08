<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/1/31
 * Time: 下午10:10
 */

namespace Minimalism\Test\AsyncTask;

use Minimalism\Async\Async;

require __DIR__ . "/../../vendor/autoload.php";


//Async::exec(function () {
//   while (true) {
//       yield 1;
//   }
//});

///*
$stackoverflow = function($n) {
    for ($i = 0; $i < $n; $i++) {
        $mu = memory_get_usage();
        echo "$i:$mu\n";
        yield $i;
    }
};
// Fatal error: Maximum function nesting level of '256' reached, aborting!
// Async::exec($stackoverflow(256));

// TODO
// i = 20944 coredump !!!
ini_set("xdebug.max_nesting_level", PHP_INT_MAX);
Async::exec($stackoverflow(30000));
//*/