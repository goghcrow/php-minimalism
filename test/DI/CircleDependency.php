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