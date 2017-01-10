<?php

namespace Minimalism\Test\DI;

use Minimalism\DI\CircleDepDemo;

require __DIR__ . "/../../src/DI/CircleDepDemo.php";



interface IA
{
    public function funcA();
}

interface IB
{
    public function funcB();
}

class A implements IA
{
    /* @var $b IB */
    private $b;

    public function __construct()
    {
        $this->b = CircleDepDemo::make(IB::class);
    }

    public function funcA()
    {
        echo spl_object_hash($this) . ":" . __FUNCTION__, PHP_EOL;
    }

    public function invokeBMethod()
    {
        $this->b->funcB();
    }
}

class B implements IB
{
    /* @var $a IA */
    private $a;

    public function __construct()
    {
        $this->a = CircleDepDemo::makeLazy(IA::class);
    }

    public function funcB()
    {
        echo spl_object_hash($this) . ":" . __FUNCTION__, PHP_EOL;
    }

    public function invokeAMethod()
    {
        $this->a->funcA();
    }
}


CircleDepDemo::register(IA::class, function(...$args) {
    return new A(...$args);
});

CircleDepDemo::register(IB::class, function(...$args) {
    return new B(...$args);
});



(new A())->invokeBMethod();
(new B())->invokeAMethod();
(new B())->invokeAMethod();
(new B())->invokeAMethod();
