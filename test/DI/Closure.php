<?php

namespace Minimalism\DI\Test;


use Minimalism\DI\Closure;

require __DIR__ . "/../../src/DI/Closure.php";

////////////////////////////////////////////////////////////////////////////////////
// 1 普通函数转Closure
function my_callback_function() { return "hello world!"; }
$c = Closure::fromCallable(__NAMESPACE__ . "\\my_callback_function");
assert($c() === "hello world!");


////////////////////////////////////////////////////////////////////////////////////
class MyClass {
    public $prop = "Hello World!";
    public static $staticProp = "Static Hello World!";
    public function myCallbackMethod() {
        return $this->prop;
    }
    public static function myStaticCallbackMethod() {
        return static::$staticProp;
    }

    public static $invokeProp = "Invoke Hello World";
    public function __invoke() {
        return self::$invokeProp;
    }
}

// 2 静态方法转Closure
$c = Closure::fromCallable([MyClass::class, "myStaticCallbackMethod"]);
assert($c() === MyClass::$staticProp);

// 3 静态方法转Closure
$c = Closure::fromCallable(MyClass::class . "::myStaticCallbackMethod");
assert($c() === MyClass::$staticProp);

$myClass = new MyClass;

// 4 实例方法转Closure
$c = Closure::fromCallable([$myClass, "myCallbackMethod"]);
assert($c() === $myClass->prop);


MyClass::$staticProp = "New Static Hello World!";
$myClass->prop = "New Hello World!";


$c = Closure::fromCallable([MyClass::class, "myStaticCallbackMethod"]);
assert($c() === MyClass::$staticProp);

$c = Closure::fromCallable(MyClass::class . "::myStaticCallbackMethod");
assert($c() === MyClass::$staticProp);

$c = Closure::fromCallable([$myClass, "myCallbackMethod"]);
assert($c() === $myClass->prop);

$newMyClass = new MyClass;
$newMyClass->prop = "New Hello World!";
$c = Closure::fromCallable([$newMyClass, "myCallbackMethod"]);
assert($c() === $newMyClass->prop);

// 5 实现__invoke方法的对象实例转Closure
$c = Closure::fromCallable($myClass);
assert($c() === MyClass::$invokeProp);


////////////////////////////////////////////////////////////////////////////////////
class A {
    public static $who = "A";
    public static function who() {
        return self::$who;
    }
    public $name = "A";
    public function hello() {
        return "A Hello " . $this->name;
    }
    public function helloWithStatic() {
        return "A Hello " . self::$who;
    }
    public function testStatic() {
        return call_user_func("static::hello");
        // $c = Closure::fromCallable("static::hello");
        // return $c();
    }
}

class B extends A {
    public static $who = "B";
    public static function who() {
        return self::$who;
    }
    public $name = "B";
    public function hello() {
        return "B Hello " . $this->name;
    }
    public function helloWithStatic() {
        return "B Hello " . self::$who;
    }
}

// 6 父类静态方法转Closure
$c = Closure::fromCallable([B::class, "parent::who"]);
assert($c() === "A");

// 7 子类静态方法转Closure
$c = Closure::fromCallable([B::class, "self::who"]);
assert($c() === "B");

// 8 父类实例方法转Closure
// !!! 非静态量 $this绑定到 new B
$c = Closure::fromCallable([new B, "parent::hello"]);
assert($c() === "A Hello B");

// 9 子类实例方法转Closure
// !!! 静态变量 保持方法内作用域
$c = Closure::fromCallable([new B, "parent::helloWithStatic"]);
assert($c() === "A Hello A");