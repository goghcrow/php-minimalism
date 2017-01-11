<?php

namespace Minimalism\Test;


use Minimalism\TimingWheel;

require __DIR__ . "/../src/TimingWheel.php";

$interval = 1000; // ms
$wheelCount = 5;  // n interval
$tw = new TimingWheel($wheelCount, $interval);

function pn($timerId = null) {
    if ($timerId) {
        echo microtime(true), ": $timerId: done", PHP_EOL;
    } else {
        echo microtime(true), PHP_EOL;
    }
}


pn();
$tw->after(2000, function(TimingWheel $tw, $timerId) {
    pn($timerId);

    for ($i = 1; $i <= 5; $i++) {
        $tw->after($i * 1000, function(TimingWheel $tw, $timerId) use($i) {
            pn($timerId);
            if ($i === 4) {
                $tw->cancel("b5");
            }
        }, "b$i");
    }
});