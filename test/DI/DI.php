<?php

namespace Minimalism\Test\DI;

use Minimalism\DI\DI;
use Minimalism\DI\DIException;

require __DIR__ . "/../../src/DI/DI.php";
require __DIR__ . "/../../src/DI/Closure.php";


$di = new DI([
    "x" => 1,
    "conf" => ["v" => 2],
]);

assert($di(function($x, $conf) {
    return $x + $conf["v"];
}) === 3);

assert($di->make("x") === 1);
assert($di->make("conf")["v"] === 2);

try {
    $di(function($not_exist) {});
    assert(false);
} catch (DIException $ex) {
    assert($ex);
}

//=================================================================
// 接口实现无构造函数
interface IHelloService1 {
    public function hello($name);
}

class HelloService1 implements IHelloService1 {
    public function hello($name) {
        return "hello $name";
    }
}

// 添加接口服务依赖
$di = new DI([
    IHelloService1::class => HelloService1::class,     
]);

assert($di(function(IHelloService1 $hello) {
   return $hello->hello(__NAMESPACE__);
}) === "hello " . __NAMESPACE__);

$closure = $di->inject(function(IHelloService1 $hello) {
    return $hello->hello(__NAMESPACE__);
});

assert($closure() === "hello " . __NAMESPACE__);

assert($di->make(IHelloService1::class) instanceof HelloService1);



//=================================================================
// 接口实现有构造函数
interface IHelloService2 {
    public function hello();
}

class HelloService2 implements IHelloService2 {
    private $name;
    public function __construct($name) {
        assert($name === __NAMESPACE__);
        $this->name = $name;
    }

    public function hello() {
        return "hello {$this->name}";
    }
}

$di = new DI([
    "name" => __NAMESPACE__,
    IHelloService2::class => HelloService2::class,
]);

assert($di(function(IHelloService2 $hello) {
        return $hello->hello();
}) === "hello " . __NAMESPACE__);

assert($di->make(IHelloService2::class)->hello() === "hello " . __NAMESPACE__);

//=================================================================
interface ITest1 {}
class Test1 {}

$di = new DI([
    ITest1::class => "Not_EXIST_CLASS"
]);
try {
    $di(function(ITest1 $test) {});
    assert(false);
} catch (DIException $ex) {
    assert($ex->getMessage() === 'Interface "Minimalism\Test\DI\ITest1" Implements Class "Not_EXIST_CLASS" Not Found');
}


//=================================================================
interface ITest2 {}
class Test2 {}

$di = new DI([
    ITest2::class => Test2::class,
]);

try {
    $di(function(ITest2 $test) {});
    assert(false);
} catch (DIException $ex) {
    assert($ex->getMessage() === 'Minimalism\Test\DI\Test2 Does Not Implements Minimalism\Test\DI\ITest2');
}

//=================================================================
// 类 有构造函数
class HelloService3 {
    private $helloServ;
    public function __construct(IHelloService2 $hello) {
        $this->helloServ = $hello;
    }
    public function hello() {
        return $this->helloServ->hello();
    }
}

$di = new DI([
    "name" => __NAMESPACE__,
    IHelloService2::class => HelloService2::class,
]);

assert($di(function(HelloService3 $hello) {
    return $hello->hello();
}) === "hello " . __NAMESPACE__);

assert($di->make(IHelloService2::class)->hello() === "hello " . __NAMESPACE__);

//=================================================================
// 类 子类有构造函数

abstract class baseHelloService {
    protected $helloServ;
    public function __construct(IHelloService2 $hello) {
        $this->helloServ = $hello;
    }
    abstract  public function hello();
}

class HelloService4 extends baseHelloService {
    private $who;
    public function __construct(IHelloService2 $hello, $who) {
        parent::__construct($hello);
        $this->who = $who;
    }

    public function hello() {
        return $this->who . " say " . $this->helloServ->hello();
    }
}


$di = new DI([
    "name" => __NAMESPACE__,
    IHelloService2::class => HelloService2::class,
    "who" => "someone",
    // 父类或者虚类需要指定实现类
    HelloService3::class => HelloService4::class,
]);


assert($di(function(HelloService4 $hello) {
    return $hello->hello();
}) === $di->make("who") . " say hello " . __NAMESPACE__);


assert($di->make(HelloService4::class)->hello() === $di->make("who") . " say hello " . __NAMESPACE__);

//=================================================================
// singleton
interface SingletonService {
    public function getCount();
}
class Singleton implements SingletonService {
    static $count = 0;
    public function __construct($name) {
        assert(++self::$count <= 1);
    }
    public function getCount() {
        return self::$count;
    }
}

$di = new DI([
    "name" => __NAMESPACE__,
    SingletonService::class => [
        "class" => Singleton::class,
        "single"=> true,
    ]
]);

assert($di(function(SingletonService $singleton) {
    return $singleton->getCount();
}) === 1);
assert($di(function(SingletonService $singleton) {
        return $singleton->getCount();
}) === 1);

//=================================================================

// inject
interface XService {
    public function X($arg);
}

class XServiceImpl implements XService {
    public function X($arg) {
        return __METHOD__ . " $arg";
    }
}

$di = new DI([
    XService::class => XServiceImpl::class,
]);

function doSomething(XService $xService) {
    return $xService->x(__FUNCTION__);
}

$doSomething = $di->inject(__NAMESPACE__ . "\\doSomething");

assert($doSomething() === __NAMESPACE__ . "\\XServiceImpl::X " . __NAMESPACE__ . "\\doSomething");
