<?php

namespace Minimalism\Scheme\Test;

use Minimalism\Scheme\Scope;
use Minimalism\Scheme\Value\Value;

require_once __DIR__ . "/../src/Scope.php";
require_once __DIR__ . "/../src/Value/Value.php";

class TestValue extends Value {
    public $value;
    public function __construct($value)
    {
        $this->value = $value;
    }

    public function __toString()
    {
        return "testValue";
    }
}

$parent = new Scope();
$local = new Scope($parent);


$parent->putValue("foo", new TestValue("bar"));
$parent->putValue("hello", new TestValue("world"));
assert($parent->lookupValue("foo")->value === "bar");
assert($parent->lookupLocalValue("foo")->value === "bar");
assert($parent->lookupValue("hello")->value === "world");
assert($parent->lookupLocalValue("hello")->value === "world");
assert($local->lookupValue("foo")->value === "bar");
assert($local->lookupLocalValue("foo") === null);
assert($local->lookupValue("hello")->value === "world");
assert($local->lookupLocalValue("hello") === null);
