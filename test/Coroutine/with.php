<?php

namespace Minimalism\Test\Coroutine;

use function Minimalism\Coroutine\with;

require __DIR__ . "/../../vendor/autoload.php";


$obj = new \stdClass();
$obj->id = 42;

$c = \Closure::bind(function() {
    assert($this->id === 42);
}, $obj);
$c();

with(function() {
    assert($this->id === 42);
}, $obj);

with(function() {
    assert($this->id === 42);
}, ["id" => 42]);