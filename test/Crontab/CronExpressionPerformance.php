<?php

namespace Minimalism\Test\Crontab;


use Minimalism\Crontab\CronExpression;
use ReflectionClass;

require __DIR__ . "/../../src/Crontab/CronExpression.php";


$class = new ReflectionClass(CronExpression::class);

$strtol = $class->getMethod("strtol")->getClosure();
$fillZero = $class->getMethod("fillZero")->getClosure();
$parseLine = $class->getMethod("parseLine")->getClosure();

$parseField_ = $class->getMethod("parseField_")->getClosure();
$parseField = $class->getMethod("parseField")->getClosure();


function performance(\Closure $closure) {
    $start = microtime(true);
    for ($i = 0; $i < 30000; $i++) {
        $closure($i);
    }
    echo microtime(true) - $start, PHP_EOL;
}



performance(function() use($parseField) {
    $parseField("1-4/1,6,9-12", 12, 1);
});

performance(function() use($parseField_) {
    $parseField_("1-4/1,6,9-12", 12, 1);
});



$weekNames = ["sun", "mon", "tue", "wed", "thu", "fri", "sat"];
performance(function() use($weekNames, $parseField) {
    $parseField("Mon,Wed-Fri", 7, 1, $weekNames);
});

performance(function() use($weekNames, $parseField_) {
    $parseField_("Mon,Wed-Fri", 7, 1, $weekNames);
});
