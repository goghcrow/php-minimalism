<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/1/31
 * Time: 下午10:10
 */

namespace Minimalism\Test\A;



use function Minimalism\A\Core\async;

require __DIR__ . "/../../vendor/autoload.php";


//async(function () {
//   while (true) {
//       yield 1;
//   }
//});

/*
$stackoverflow = function($n) {
    for ($i = 0; $i < $n; $i++) {
        $mu = memory_get_usage();
        echo "$i:$mu\n";
        yield $i;
    }
};
// Fatal error: Maximum function nesting level of '256' reached, aborting!
// async($stackoverflow(256));