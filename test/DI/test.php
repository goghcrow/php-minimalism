<?php

namespace Minimalism\Test\DI;

///*
// ===================================================================================
class CA {
    public $x = "A";
    public function m() {
        return "Base-" . $this->x;
    }

    public function scall() {
        return call_user_func("static::m");
    }
}

class CB extends CA {
    public $x = "B";
    public function m() {
        return "Sub-" . $this->x;
    }

    public function scall() {
        return parent::scall();
    }
}

$a = new CA;
$b = new CB;

assert($a->scall() === "Base-A");
assert($b->scall() === "Sub-B");

$clazz = new \ReflectionClass($b);
$method = $clazz->getParentClass()->getMethod("m");
$closure = $method->getClosure($b);
assert(is_callable([$b, "parent::m"], false, $callName) === true);
// echo $callName;
assert($closure() === "Base-B");

class CC {
    public $x = "C";
    public function cm() {
        call_user_func([new CB, "static::m"]);
    }
}




// ===================================================================================
class BaseClass {
    public static function staticMethod () {
        return __METHOD__;
    }
}

class ParentClass extends BaseClass {
    public static function staticMethod () {
        return __METHOD__;
    }
    public function method () {
        return __METHOD__;
    }
}

class SubClass extends ParentClass {
    public static function staticMethod () {
        return __METHOD__;
    }
    public function method () {
        return __METHOD__;
    }
}

$selfCall = [SubClass::class, "self::staticMethod"];
$parentCall = [SubClass::class, "parent::staticMethod"];
assert(is_callable($selfCall) === true);
assert(is_callable($parentCall) === true);

assert(call_user_func($selfCall) === __NAMESPACE__ . "\\SubClass::staticMethod");
assert(call_user_func($parentCall) === __NAMESPACE__ . "\\ParentClass::staticMethod");
assert(call_user_func([ParentClass::class, "parent::staticMethod"]) === __NAMESPACE__ . "\\BaseClass::staticMethod");


// $parentCall = [SubClass::class, "parent::staticMethod"];
$clazz = new \ReflectionClass(SubClass::class);
$method = $clazz->getParentClass()->getMethod("staticMethod");
$closure = $method->getClosure();
// echo $closure(); // parent


$prototype = $method->getPrototype();
$protoClosure = $prototype->getClosure();
// echo $protoClosure(); // base


// ===================================================================================
class InvokableClass {
    public function __invoke() {}
}
$invokable = new InvokableClass;
assert(is_callable($invokable) === true);

// ===================================================================================
class StaticCallable  {
    public static function method() {
        return __METHOD__;
    }
}
//new \ReflectionFunction(StaticCallable::class . "::method");
$method = new \ReflectionMethod(StaticCallable::class, "method");
$closure = $method->getClosure(null);
$closure();

// ===================================================================================
$staticClosure = static function() {
    echo $this->prop;
};

$normalClosure = function() {
    return $this->prop;
};

class scope { public $prop = "hello"; }

$newThis = new \stdClass;
$newThis->prop = "hello";

// Warning: Cannot bind an instance to a static closure
// $staticClosure->call($newThis);
// $staticClosure->call(new scope);

// Warning:  Cannot bind closure to scope of internal class Closure
// $normalClosure->call($normalClosure);

// PHP7
// assert($normalClosure->call(new scope) === "hello");
// ===================================================================================


//abstract class absClass {}
//$clazz = new \ReflectionClass(absClass::class);
//try {
//    $clazz->newInstanceWithoutConstructor();
//    assert(false);
//} catch (\Throwable $e) {
//    assert($e);
//}

class privateCtor {
    private function __construct(){}
}
//$clazz = new \ReflectionClass(privateCtor::class);
//try {
//    $clazz->newInstance();
//    assert(false);
//} catch (\Throwable $e) {
//    assert($e);
//}
// ===================================================================================


Interface I {}
class A {}
class B extends A implements I{}
assert(is_subclass_of(B::class, A::class) === true);
// 类不能存在返回false
assert(is_subclass_of("NOT_EXIST", "NOT_EXIST") === false);


function xxx() {
    return __FUNCTION__;
}
assert(xxx() === __NAMESPACE__ . "\\xxx");

interface TestInterface {

}


function foo(TestInterface $a) { }

$functionReflection = new \ReflectionFunction(__NAMESPACE__ . '\foo');
$parameters = $functionReflection->getParameters();
$aParameter = $parameters[0];

$type = $aParameter->getClass()->name;
assert(interface_exists($type));
assert($type === TestInterface::class);
//*/