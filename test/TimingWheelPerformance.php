<?php

namespace Minimalism\Test;


use Minimalism\TimingWheel;

require __DIR__ . "/../src/TimingWheel.php";


// 时间轮在定时器数量非常大的情况下也能保持非常稳定
// 但是精度可能没有原生方法高
// TODO 看误差分布

ini_set("memory_limit", "500M");

function _test($n, $after, $desc = "") {
    $ret = [];
    swoole_timer_after(6000, function() use(&$ret, $desc) {
        sort($ret);
        echo str_pad($desc, 40, " ", STR_PAD_RIGHT), number_format(reset($ret), 5), " ~ ", number_format(end($ret), 5), PHP_EOL;
    });

    while (--$n >= 0) {
        $start = microtime(true);
        $expect = mt_rand(1, 50) * 100;
        $after($expect, function() use($start, $expect, $n, &$ret) {
            $realCost = microtime(true) - $start;
            $ret[] = $realCost * 1000 - $expect;
        });
    }
}


function test($n) {
    $pid1 = pcntl_fork();
    if ($pid1 < 0) {
        exit(1);
    } else if ($pid1 === 0) {
        _test($n, "\\swoole_timer_after", "swoole_timer_after");
        exit;
    }

    $pid2 = pcntl_fork();
    if ($pid2 < 0) {
        exit(1);
    } else if ($pid2 === 0) {
        $interval = 500; // ms
        $wheelCount = 10;  // n interval
        $tw = new TimingWheel($wheelCount, $interval);
        _test($n, [$tw, "after"], TimingWheel::class);
        exit;
    }

    pcntl_waitpid($pid1, $status);
    pcntl_waitpid($pid2, $status);
}


// Timer为zan中的Timer类的数据, case已经移除

// test(1000);

/*
Timer               -0.96712 ~ 3,924.43910
TimingWheel         -24.54495 ~ 399.73702
swoole_timer_after  -1.94192 ~ 13.40008

swoole_timer_after  -1.98097 ~ 2,919.88411
Timer               -1.95403 ~ 3.82190
TimingWheel         -34.82819 ~ 398.28911

swoole_timer_after  -1.97110 ~ 1,925.93780
TimingWheel         -36.00287 ~ 398.27504
Timer               -1.96185 ~ 2,932.45401

swoole_timer_after  -1.95694 ~ 3,911.54900
TimingWheel         -25.14505 ~ 397.77699
Timer               -1.93000 ~ 926.66116

TimingWheel         -18.33797 ~ 399.25208
Timer               -0.95000 ~ 920.00904
swoole_timer_after  -0.97499 ~ 912.87103
*/


//test(10000);

/*
swoole_timer_after  -1.97687 ~ 22.44782
TimingWheel         -186.03086 ~ 399.13883
Timer               -1.96309 ~ 127.82803

swoole_timer_after  -1.97401 ~ 3,023.27194
TimingWheel         -178.06005 ~ 399.09496
Timer               -1.96009 ~ 115.68394

TimingWheel         -176.99814 ~ 398.81601
swoole_timer_after  -1.97597 ~ 2,018.57796
Timer               -1.96195 ~ 3,115.91187

swoole_timer_after  -1.97997 ~ 10.28981
TimingWheel         -176.41592 ~ 398.99697
Timer               -1.95818 ~ 128.13797

swoole_timer_after  -1.97496 ~ 60.45709
TimingWheel         -193.74299 ~ 399.19009
Timer               -1.95308 ~ 2,121.06099
*/



// test(100000);

/*
TimingWheel         -301.32604 ~ 2,563.67793
swoole_timer_after  -1.94688 ~ 1,637.33187
*/