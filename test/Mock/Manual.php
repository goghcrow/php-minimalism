<?php

namespace Minimalism\Test\Mock;

use ReflectionMethod;
use Minimalism\Mock\Mock;

require __DIR__ . "/../../src/Mock/Mock.php";
require __DIR__ . "/TestClass.php";
require __DIR__ . "/TestClassMock.php";


// 1. example: 手动mock


////////////////////////////////////////////////////////////////////////
$mock = new Mock(TestClass::class);

// mock say 方法
$mock->method("say", function($arg) {
    return "Mock\\say" . "($arg)";
}, ReflectionMethod::IS_PUBLIC);

// 将private static 方法 修改成public static方法
$mock->method("staticMethod", function() {
    $args = implode(", ", func_get_args());
    return "Mock\\say" . "($args)";
}, ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC);
////////////////////////////////////////////////////////////////////////

$testClass = new TestClass("xiaofeng");
assert("Mock ==> " . $testClass->say("xiaofeng") === 'Mock ==> Mock\say(xiaofeng)');

assert("UnMock " . TestClass::class . "::say" === 'UnMock Minimalism\Test\Mock\TestClass::say');
$mock->restore("say");

assert($testClass->say("xiaofeng") === 'Minimalism\Test\Mock\TestClass::say(xiaofeng)');

// 没有错,注释掉,IDE报错扎眼
// echo "Mock ==> " , $testClass->staticMethod(1, 2, 3), PHP_EOL;
$mock->restore("staticMethod");



$mock->method("getName", function($addOneArg) {
    return "mock $addOneArg";
});
assert($testClass->getName("arg") === 'mock arg');

$mock->restore("getName");
assert($testClass->getName("arg") === 'xiaofeng');
