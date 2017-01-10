<?php

namespace Minimalism\Test;


use Minimalism\Backoff;

require __DIR__ . "/../src/Backoff.php";

$min = 2000;
$max = 1000 * 60 * 10;
$factor = 2;
$jitter = 0.3;
$maxAttempt = 5;

$backoff = new Backoff($min, $max, $factor, $jitter);
for ($i = 0; $i < $maxAttempt; $i++) {
    echo $backoff->duration($i + 1), "\n";
}
