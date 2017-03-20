<?php


function λ()
{

}

function β()
{

}

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

class Proto
{
    public $prop;

    public function __construct($prop)
    {
        $this->prop = $prop;
    }

    public function getClosure()
    {
        return function() {
            return $this->prop;
        };
    }
}

$prop = new Proto("hello");
$closure = $prop->getClosure();
echo $closure(), "\n";
$prop->prop = "world";
echo $closure(), "\n";

$clone = clone $prop;
$clone->prop = "abc";
$closure = $closure->bindTo($clone, Proto::class);
echo $closure(), "\n";


// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

$obj = new \__PHP_Incomplete_Class();
assert(is_object($obj) === false);

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

class Call
{
    public static function __callStatic($name, $arguments)
    {
        var_dump($name);
    }
}

call_user_func([Call::class, "method\0trunked"]);

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

class A
{

}

class B extends A {
    public $public = 1;
    protected $protected = 2;
    private $private = 3;
}

$c = function() {
    $this->public;
    $this->protected; // 可以访问 protected
    // $this->private;
};

$c = $c->bindTo(new B, A::class);
$c();


// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

// ini_set("xdebug.max_nesting_level", PHP_INT_MAX);
// function r(){$self = __FUNCTION__;$self();}r();

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
