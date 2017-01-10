<?php

namespace Minimalism\Test\Mock;


class TestClass
{
    private $name;

    public function __construct($name) {
        $this->name = $name;
    }

    public function getName() {
        return $this->name;
    }

    public function say($something) {
        return __METHOD__ . "($something)";
    }
    
    private static function staticMethod() {
        $args = implode(", ", func_get_args());
        return __METHOD__ . "($args)";
    }

    private function privateMethod() {
        $args = implode(", ", func_get_args());
        return __METHOD__ . "($args)";
    }
}