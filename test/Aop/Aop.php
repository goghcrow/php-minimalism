<?php

namespace Minimalism\Test\Aop;

use Exception;
use Minimalism\Aop\Aop;
use SplStack;

require __DIR__ . "/../../src/Aop/Aop.php";
require __DIR__ . "/../../src/Aop/ProxyClass.php";
require __DIR__ . "/../../src/Aop/ProxyPool.php";

defined("EOL") or define("EOL", PHP_EOL);

$aop = new Aop(new SplStack());

$aop->addAdvice(Aop::BEFORE, Aop::X, 'push',
    function(&$args) {
        echo "Aop: push $args[0]", EOL;
        // before修改参数
        $args[0] = "#push_{$args[0]}";
    }
);

$aop->addAdvice(Aop::AFTER, Aop::X, 'pop',
    function(&$ret) {
        echo "Aop: pop $ret", EOL;
        // after修改返回值
        $ret = "#ret_{$ret}";
    }
);

/* @var $stack SplStack*/
$stack = $aop->getProxy();



function assert_ob(\Closure $func, $expect) {
    ob_start();
    $func();
    $output = trim(preg_replace("/\R/", PHP_EOL, ob_get_clean()), PHP_EOL);
    $expect = trim(preg_replace("/\R/", PHP_EOL, $expect), PHP_EOL);
    assert($output === $expect);
}



assert_ob(function() use($stack) {
    $stack->push("test_push");
    assert($stack->pop() === "#ret_#push_test_push");
}, <<<'OUT'
Aop: push test_push
Aop: pop #push_test_push
OUT
);


////////////////////////////////////////////////////////////////////////////////////////


class A {
	public $property;

	public function __construct($x) {
		$this->x = $x;
	}

	public function func($arg) {
		echo 'call func: $this->x = ' . $this->x . EOL;
		return $arg;
	}

	public function _throw() {
		throw new Exception("Error Processing Request", 1);
	}

	public function around($arg) {
		return $arg;
	}
}

$oldA = new A(M_PI);
$aop = new Aop($oldA);


// !!! 赋值前可修改，参数必须写引用
$aop->addAdvice(Aop::BEFORE, Aop::W, 'property', function(&$val) {
    echo "aop: before set property $val", EOL;
    $val = 'before_' . $val;
});
$aop->addAdvice(Aop::AFTER, Aop::W, 'property', function($val) {
    echo "aop: after set property: $val", EOL;
});
$aop->addAdvice(Aop::BEFORE, Aop::R, 'property', function() {
    echo "aop: before get property", EOL;
});
// !!! 读取之后可修改返回值，参数必须写引用
$aop->addAdvice(Aop::AFTER, Aop::R, 'property', function(&$val) {
    echo "aop: after get property: $val", EOL;
    $val = 'modified_' . $val;
});




// 请求前修改参数，参数必须写引用
$aop->addAdvice(Aop::BEFORE, Aop::X, 'func', function(&$args) {
    echo "aop: before call func", EOL;
	$args[0] = "before_" . $args[0];
});

// 请求后修改返回值，参数必须写引用
$aop->addAdvice(Aop::AFTER, Aop::X, 'func', function(&$ret) {
    echo "aop: after call func1", EOL;
    $ret .= "_after1";
});

$aop->addAdvice(Aop::AFTER, Aop::X, 'func', function(&$ret) {
    echo "aop: after call func1", EOL;
	$ret .= "_after2";
});




// 执行异常时触发
$aop->addAdvice(Aop::EXCEPTION, Aop::X, '_throw', function(Exception $e, &$ret) {
	echo 'aop: exception in _throw -> ' . $e->getMessage(),  EOL;
	// 异常发生后修改返回值
	$ret = 'exceptionResult';
});




// 替换原方法
$aop->addAdvice(Aop::AROUND, Aop::X, 'around', function($args, callable $parent, &$lastRet) {
    echo 'aop: around',  EOL;
    $oldRet = $parent($args);
    return $oldRet . '_around1';
});

$aop->addAdvice(Aop::AROUND, Aop::X, 'around', function($args, callable $process, &$lastRet) {
    echo 'aop: around',  EOL;
    return $lastRet . '_around2';
});




assert_ob(function() use($aop) {
    /* @var $newA A */
    $newA = $aop->getProxy();

    $newA->property = "world";
    echo $newA->property . EOL;

    echo EOL;

    echo $newA->func('#hello#') . EOL;

    echo EOL;

    echo $newA->_throw() . EOL;

    echo $newA->around('#test_around#') . EOL;
}, <<<'OUT'
aop: before set property world
aop: after set property: before_world
aop: before get property
aop: after get property: before_world
modified_before_world

aop: before call func
call func: $this->x = 3.1415926535898
aop: after call func1
aop: after call func1
before_#hello#_after1_after2

aop: exception in _throw -> Error Processing Request
exceptionResult
aop: around
aop: around
#test_around#_around1_around2
OUT
);