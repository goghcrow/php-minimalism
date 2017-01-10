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