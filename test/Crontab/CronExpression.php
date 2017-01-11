<?php

namespace Minimalism\Test\Crontab;


use Minimalism\Crontab\CronExpression;
use ReflectionClass;
use RuntimeException;

require __DIR__ . "/../../src/Crontab/CronExpression.php";

$class = new ReflectionClass(CronExpression::class);

$strtol = $class->getMethod("strtol")->getClosure();
$fillZero = $class->getMethod("fillZero")->getClosure();
$parseField = $class->getMethod("parseField")->getClosure();
$parseLine = $class->getMethod("parseLine")->getClosure();





//////////////////////////////////////////////////////////////////////
assert($fillZero(7) === array (
        0 => 0,
        1 => 0,
        2 => 0,
        3 => 0,
        4 => 0,
        5 => 0,
        6 => 0,
    ));

assert($fillZero(7, 1) === array (
        1 => 0,
        2 => 0,
        3 => 0,
        4 => 0,
        5 => 0,
        6 => 0,
        7 => 0,
    ));

//////////////////////////////////////////////////////////////////////


// PHP_INT_MAX 64bit 9223372036854775807
// 1.
assert(intval(strval(PHP_INT_MAX) . 1) === PHP_INT_MAX);
// 2.
assert(intval("9223372036854775808") === PHP_INT_MAX);
// 3.
$str = strval(PHP_INT_MAX) . "non_digit";
$long = $strtol($str, $str);
assert($long === PHP_INT_MAX);
assert($str === "non_digit");
// 4.
$str = strval(PHP_INT_MAX) . "123" . "non_digit";
$long = $strtol($str, $str);
assert($long === PHP_INT_MAX);
assert($str === "123non_digit");

$str = "-123hello";
assert($strtol($str, $str) === -123);
assert($str === "hello");
//////////////////////////////////////////////////////////////////////

try {
    $parseLine("");
    assert(false);
} catch (RuntimeException $ex) {}

try {
    $parseLine("* * * * *");
    assert(false);
} catch (RuntimeException $ex) {}

assert($parseLine("* * * * * *") === array (
        0 => '*',
        1 => '*',
        2 => '*',
        3 => '*',
        4 => '*',
        5 => '*',
    ));

assert($parseLine("* * * * * * other") === array (
        0 => '*',
        1 => '*',
        2 => '*',
        3 => '*',
        4 => '*',
        5 => '*',
    ));

assert($parseLine("* 30 3 1,7,14,21,26 * *") === array (
        0 => '*',
        1 => '30',
        2 => '3',
        3 => '1,7,14,21,26',
        4 => '*',
        5 => '*',
    ));

//////////////////////////////////////////////////////////////////////

assert($parseField("*", 6, 0) === array (
        0 => 1,
        1 => 1,
        2 => 1,
        3 => 1,
        4 => 1,
        5 => 1,
    ));

assert($parseField("*", 6, 1) === array (
        1 => 1,
        2 => 1,
        3 => 1,
        4 => 1,
        5 => 1,
        6 => 1
    ));

$weekNames = ["sun", "mon", "tue", "wed", "thu", "fri", "sat"];
assert($parseField("sun-sat", 7, 0, $weekNames) === array (
        0 => 1,
        1 => 1,
        2 => 1,
        3 => 1,
        4 => 1,
        5 => 1,
        6 => 1,
    ));

$weekNames = ["sun", "mon", "tue", "wed", "thu", "fri", "sat"];
assert($parseField("sun-sat", 7, 1, $weekNames) === array (
        1 => 1,
        2 => 1,
        3 => 1,
        4 => 1,
        5 => 1,
        6 => 1,
        7 => 1,
    ));

assert($parseField("1-4/2,6,9-11", 12) === array (
        0 => 0,
        1 => 1,
        2 => 0,
        3 => 1,
        4 => 0,
        5 => 0,
        6 => 1,
        7 => 0,
        8 => 0,
        9 => 1,
        10 => 1,
        11 => 1,
    ));


assert($parseField("*/2", 6) === array (
        0 => 1,
        1 => 0,
        2 => 1,
        3 => 0,
        4 => 1,
        5 => 0,
    ));

assert($parseField("*/2", 6, 1) === array (
        1 => 1,
        2 => 0,
        3 => 1,
        4 => 0,
        5 => 1,
        6 => 0,
    ));

assert($parseField("1,2,3", 6) === array (
        0 => 0,
        1 => 1,
        2 => 1,
        3 => 1,
        4 => 0,
        5 => 0,
    ));


//try {
//    $parseField("6", 6);
//    assert(false);
//} catch (\Exception $ex) {}


assert($parseField("5", 6) === array (
        0 => 0,
        1 => 0,
        2 => 0,
        3 => 0,
        4 => 0,
        5 => 1,
    ));
assert($parseField("0", 6) === array (
        0 => 1,
        1 => 0,
        2 => 0,
        3 => 0,
        4 => 0,
        5 => 0,
    ));

//try {
//    var_export($parseField("* ", 6));
//} catch (\RuntimeException $ex) {}

assert($parseField("1-4/1,6,9-12", 12, 1) === array (
        1 => 1,
        2 => 1,
        3 => 1,
        4 => 1,
        5 => 0,
        6 => 1,
        7 => 0,
        8 => 0,
        9 => 1,
        10 => 1,
        11 => 1,
        12 => 1,
    ));

try {
    $parseField("1-4/1,6,9-13", 12, 1);
} catch (\Exception $ex) {}



//var_dump($parseField("*/0", 3));exit;
//assert($parseField("*/-1", 3) === array (
//        0 => 1,
//        1 => 0,
//        2 => 0,
//    ));

//assert($parseField("*/0", 3) === array (
//        0 => 1,
//        1 => 0,
//        2 => 0,
//    ));
assert($parseField("*/1", 3) === array (
        0 => 1,
        1 => 1,
        2 => 1,
    ));
assert($parseField("*/2", 3) === array (
        0 => 1,
        1 => 0,
        2 => 1,
    ));
assert($parseField("*/3", 3) === array (
        0 => 1,
        1 => 0,
        2 => 0,
    ));


$weekNames = ["sun", "mon", "tue", "wed", "thu", "fri", "sat"];
assert($parseField("sun-sat", 7, 0, $weekNames) === array (
        0 => 1,
        1 => 1,
        2 => 1,
        3 => 1,
        4 => 1,
        5 => 1,
        6 => 1,
    ));

assert($parseField("Mon,Wed-Fri", 7, 0, $weekNames) === array (
        0 => 0,
        1 => 1,
        2 => 0,
        3 => 1,
        4 => 1,
        5 => 1,
        6 => 0,
    ));

assert($parseField("Mon,Wed-Fri", 7, 1, $weekNames) === array (
        1 => 0,
        2 => 1,
        3 => 0,
        4 => 1,
        5 => 1,
        6 => 1,
        7 => 0,
    ));


//////////////////////////////////////////////////////////////////////
