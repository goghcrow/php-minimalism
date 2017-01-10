# Mock Impl By Uopz

**PHP5版本, 依赖uopz扩展**

1. mock 某个类的任意方法
2. 实现用一个类的同名方法替换需要mock的类, 同时覆盖访问修饰符
3. 取消mock

用法参见 example, 忽略IDE语法检查报错~

```
[uopz]
extension=uopz.so
uopz.overloads=1
```


**example2:**

```php
<?php

/**
 * Class TestClass
 * @package Xiaofeng\Test\Example
 */
class TestClass {
    private $name;

    public function __construct($name) {
        $this->name = $name;
    }

    public function getName() {
        return $this->name;
    }

    public function say($something) {
        return __METHOD__ . "($something)";
    }

    private static function staticMethod() {
        $args = implode(", ", func_get_args());
        return __METHOD__ . "($args)";
    }

    private function privateMethod() {
        $args = implode(", ", func_get_args());
        return __METHOD__ . "($args)";
    }
}


/**
 * Mock 类
 * Class TestClassMock
 * @package Xiaofeng\Test\Example
 */
class TestClassMock {

    // mock返回结果
    public function say($something) {
        return "Mock\\" . __METHOD__ . "($something)";
    }

    // 将 private static 修改成 public 并mock
    public function staticMethod() {
        $args = implode(", ", func_get_args());
        return "Mock\\" . __METHOD__ . "($args)";
    }

    // 将 private 修改成public 并mock返回结果
    public function privateMethod() {
        $args = implode(", ", func_get_args());
        return "Mock\\" . __METHOD__ . "($args)";
    }
}


// 用TestClassMock的同名方法mockTestClass
$mock = new MockClass(TestClass::class);
$mock->mockByObject(new TestClassMock);



// Magic!!!
// 对被mock的类透明
$testClass = new TestClass("xiaofeng");
assert($testClass instanceof TestClass);
// 调用mock后的方法
echo $testClass->say("~"), PHP_EOL;
echo $testClass->staticMethod(1,2,3), PHP_EOL;
echo $testClass->privateMethod(1,2,3), PHP_EOL;


// output:
/*
Mock\Xiaofeng\Test\Example\TestClassMock::say(~)
Mock\Xiaofeng\Test\Example\TestClassMock::staticMethod(1, 2, 3)
Mock\Xiaofeng\Test\Example\TestClassMock::privateMethod(1, 2, 3)
*/
```