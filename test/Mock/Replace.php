<?php

namespace Minimalism\Test\Mock;

use Minimalism\Mock\Mock;

require __DIR__ . "/../../src/Mock/Mock.php";
require __DIR__ . "/TestClass.php";
require __DIR__ . "/TestClassMock.php";

// 2. example: 类替换
// 用TestClassMock的同名方法mockTestClass
$mock = new Mock(TestClass::class);
$mock->replace(new TestClassMock);


//////////////////////////////////////////////////////////////////////

// Magic!!!, 对被mock的类透明

/* @var $testClass TestClassMock */
$testClass = new TestClass("xiaofeng");

assert($testClass instanceof TestClass);
// 调用mock后的方法
assert($testClass->say("~") === 'Minimalism\Test\Mock\TestClassMock::say(~)');
assert($testClass->staticMethod(1,2,3) === 'Minimalism\Test\Mock\TestClassMock::staticMethod(1, 2, 3)');
assert($testClass->privateMethod(1,2,3) === 'Minimalism\Test\Mock\TestClassMock::privateMethod(1, 2, 3)');