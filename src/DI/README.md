## DI

### Interfaces.php

~~~php
<?php
namespace Minimalism\Test\DI\App;

class _Interfaces {}

interface ServiceA {
    public function a();
}

interface ServiceB {
    public function b();
}

interface ServiceC {
    public function c();
}

interface ServiceD {
    public function d();
}

abstract class SingletonValid {
    static $count = 0;
    abstract function a();
}
~~~


### Implements.php
~~~php
<?php

namespace Minimalism\Test\DI\App;

class ServiceAImpl extends SingletonValid implements ServiceA {
    public function a() {
        return __METHOD__;
    }
}

class ServiceBImpl implements ServiceB {
    public function b() {
        return __METHOD__;
    }
}

class ServiceCImpl implements ServiceC {
    private $serviceA;
    private $serviceB;

    public function __construct(ServiceA $serviceA, ServiceB $serviceB) {
        $this->serviceA = $serviceA;
        $this->serviceB = $serviceB;
    }

    public function c() {
        return $this->serviceA->a() . " ==> " . $this->serviceB->b() . " ==> " . __METHOD__;
    }
}

class ServiceDImpl implements ServiceD {
    private $serviceC;

    public function __construct(ServiceC $serviceC) {
        $this->serviceC = $serviceC;
    }

    public function d() {
        return $this->serviceC->c() . " ==> " .  __METHOD__;
    }
}

class ModelA extends SingletonValid {
    private $serviceD;

    public function __construct(ServiceD $serviceD) {
        $this->serviceD = $serviceD;
        assert(++self::$count <= 1);
    }

    public function a() {
        return $this->serviceD->d() . " ==> " . __METHOD__;
    }
}

class XTools {
    public function x() {
        return __METHOD__;
    }
}
~~~


### app.php
~~~php
<?php

namespace Minimalism\Test\DI;

use Minimalism\DI\DI;
use Minimalism\Test\DI\App\ModelA;
use Minimalism\Test\DI\App\ServiceA;
use Minimalism\Test\DI\App\ServiceAImpl;
use Minimalism\Test\DI\App\ServiceB;
use Minimalism\Test\DI\App\ServiceBImpl;
use Minimalism\Test\DI\App\ServiceC;
use Minimalism\Test\DI\App\ServiceCImpl;
use Minimalism\Test\DI\App\ServiceD;
use Minimalism\Test\DI\App\ServiceDImpl;
use Minimalism\Test\DI\App\SingletonValid;
use Minimalism\Test\DI\App\XTools;

require __DIR__ . "/../../src/DI/DI.php";
require __DIR__ . "/../../src/DI/Closure.php";
require __DIR__ . "/App/_Interfaces.php";
require __DIR__ . "/App/_Implements.php";
require __DIR__ . "/App/XTools.php";


// =============================== CONFIG ===========================================
// 1. 接口对应的实现需要配置
// 2. 虚类对应的可实例化类也需要配置
// 3. 普通类不需要配置
// 4. 普通变量(标量,数组,对象实例)也不需要配置
// 5. 嵌套依赖会自动通过构造函数注入

$diConf = [
    ServiceA::class => [ // 接口 => 实现
        "class"     =>  ServiceAImpl::class,
        "single"    =>  true,
    ],
    SingletonValid::class => [ // 虚类 => 实现类
        "class"     => ModelA::class,
        "single"    => true,
    ],
    ServiceB::class => ServiceBImpl::class,
    ServiceC::class => ServiceCImpl::class,
    ServiceD::class => ServiceDImpl::class,

    // 配置普通变量
    "conf"          => [
        "name"      => __NAMESPACE__,
        "version"   => 0.1,
    ],
];


$app = new DI($diConf);


$serviceA1 = $app->make(ServiceA::class);
$serviceA2 = $app->make(ServiceA::class);
assert(spl_object_hash($serviceA1) === spl_object_hash($serviceA2));

$serviceB1 = $app->make(ServiceB::class);
$serviceB2 = $app->make(ServiceB::class);
assert(spl_object_hash($serviceB1) !== spl_object_hash($serviceB2));


$out = trim(<<<'OUT'
Minimalism\Test\DI V0.1
Minimalism\Test\DI\App\ServiceAImpl::a ==> Minimalism\Test\DI\App\ServiceBImpl::b ==> Minimalism\Test\DI\App\ServiceCImpl::c ==> Minimalism\Test\DI\App\ServiceDImpl::d ==> Minimalism\Test\DI\App\ModelA::a
Minimalism\Test\DI\App\XTools::x
OUT
);


function assert_ob(\Closure $func, $expect) {
    ob_start();
    $func();
    $output = trim(preg_replace("/\R/", PHP_EOL, ob_get_clean()), PHP_EOL);
    $expect = trim(preg_replace("/\R/", PHP_EOL, $expect), PHP_EOL);
    assert($output === $expect);
}

function assert_exception(\Closure $func, $exMsg) {
    try {
        $func();
        assert(false);
    } catch (\Exception $ex) {
        assert($ex->getMessage() === $exMsg, $ex->getMessage() . "\t");
    }
}

// 执行多次

assert_ob(function() use($app) {
    $app(function(SingletonValid $model, XTools $tools, $conf) {
        echo $conf["name"] . " V" . $conf["version"], PHP_EOL;
        echo $model->a(), PHP_EOL;
        echo $tools->x(), PHP_EOL;
    });
}, <<<'OUT'
Minimalism\Test\DI V0.1
Minimalism\Test\DI\App\ServiceAImpl::a ==> Minimalism\Test\DI\App\ServiceBImpl::b ==> Minimalism\Test\DI\App\ServiceCImpl::c ==> Minimalism\Test\DI\App\ServiceDImpl::d ==> Minimalism\Test\DI\App\ModelA::a
Minimalism\Test\DI\App\XTools::x
OUT
);


assert_ob(function() use($app) {
    $app(function(SingletonValid $model, XTools $tools, $conf) {
        echo $conf["name"] . " V" . $conf["version"], PHP_EOL;
        echo $model->a(), PHP_EOL;
        echo $tools->x(), PHP_EOL;
    });
}, <<<'OUT'
Minimalism\Test\DI V0.1
Minimalism\Test\DI\App\ServiceAImpl::a ==> Minimalism\Test\DI\App\ServiceBImpl::b ==> Minimalism\Test\DI\App\ServiceCImpl::c ==> Minimalism\Test\DI\App\ServiceDImpl::d ==> Minimalism\Test\DI\App\ModelA::a
Minimalism\Test\DI\App\XTools::x
OUT
);


assert_ob(function() use($app) {
    $app(function(SingletonValid $model, XTools $tools, $conf) {
        echo $conf["name"] . " V" . $conf["version"], PHP_EOL;
        echo $model->a(), PHP_EOL;
        echo $tools->x(), PHP_EOL;
    });
}, <<<'OUT'
Minimalism\Test\DI V0.1
Minimalism\Test\DI\App\ServiceAImpl::a ==> Minimalism\Test\DI\App\ServiceBImpl::b ==> Minimalism\Test\DI\App\ServiceCImpl::c ==> Minimalism\Test\DI\App\ServiceDImpl::d ==> Minimalism\Test\DI\App\ModelA::a
Minimalism\Test\DI\App\XTools::x
OUT
);


// 获取一个注入完成的闭包
$closure = $app->inject(function(SingletonValid $model, XTools $tools, $conf) {
    echo $conf["name"] . " V" . $conf["version"], PHP_EOL;
    echo $model->a(), PHP_EOL;
    echo $tools->x(), PHP_EOL;
});

assert_ob(function() use($closure) {
    $closure();
}, <<<'OUT'
Minimalism\Test\DI V0.1
Minimalism\Test\DI\App\ServiceAImpl::a ==> Minimalism\Test\DI\App\ServiceBImpl::b ==> Minimalism\Test\DI\App\ServiceCImpl::c ==> Minimalism\Test\DI\App\ServiceDImpl::d ==> Minimalism\Test\DI\App\ModelA::a
Minimalism\Test\DI\App\XTools::x
OUT
);


~~~


**自动测循环依赖**

@2016-07-27

```php
<?php

namespace Minimalism\Test\DI;


use Minimalism\DI\DI;
use Minimalism\DI\DICircleDependencyException;

require __DIR__ . "/../../src/DI/DI.php";
require __DIR__ . "/../../src/DI/Closure.php";

//////////////////////////////////////////////////////////////////////////////////////////
interface IA {}
interface IB {}

class A1 implements IA {
    private $b;
    public function __construct(B1 $b) {
        $this->b = $b;
    }
}

class B1 implements IB {
    private $a;
    public function __construct(A1 $a) {
        $this->a = $a;
    }
}

//////////////////////////////////////////////////////////////////////////////////////////

interface IAA {}

class AA implements IAA {
    private $bb;
    function __construct(BB $bb){
        $this->bb = $bb;
    }
}

class BB {
    private $cc;
    function __construct(CC $cc){
        $this->cc = $cc;
    }
}

class CC {
    private $aa;
    function __construct(AA $aa){
        $this->aa = $aa;
    }
}

class DD {
    private $ee;
    function __construct(EE $ee){
        $this->ee = $ee;
    }
}

class EE {
    private $ff;
    private $aa;
    function __construct(FF $ff, IAA $aa){
        $this->ff = $ff;
        $this->aa = $aa;
    }
}

class FF {}

//////////////////////////////////////////////////////////////////////////////////////////


$di = new DI([
    IA::class => A1::class,
    IB::class => B1::class,
    IAA::class => AA::class,
]);

try {
    $di->make(IA::class);
    assert(false);
} catch (DICircleDependencyException $ex) {
    assert($ex->getMessage() ===
        'Found Circle Dependency In Path Minimalism\Test\DI\A1 -> Minimalism\Test\DI\B1 -> Minimalism\Test\DI\A1');
}


try {
    $di->make(DD::class);
    assert(false);
} catch (DICircleDependencyException $ex) {
    assert($ex->getMessage() ===
        'Found Circle Dependency In Path Minimalism\Test\DI\DD -> Minimalism\Test\DI\EE -> Minimalism\Test\DI\FF -> Minimalism\Test\DI\AA -> Minimalism\Test\DI\BB -> Minimalism\Test\DI\CC -> Minimalism\Test\DI\AA');
}
```
