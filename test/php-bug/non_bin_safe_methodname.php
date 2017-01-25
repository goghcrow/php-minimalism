<?php


// 方法名会被\0截断

class Foo
{
    public static function __callStatic($name, $arguments)
    {
        return $name;
    }
}

assert(call_user_func([Foo::class, "\0"]) === "");
assert(call_user_func([Foo::class, "abc\0def"]) === "abc");
