<?php

namespace Minimalism\DI;

/**
 * 循环依赖解决的思考, 一个示例Demo
 * 通过在循环依赖的一方惰性实例化可以处理循环依赖的问题
 */


class CircleDepDemo
{
    protected static $proxyPool = [];
    protected static $proxyRefCount = [];
    protected static $makePool = [];
    protected static $instancePool = [];

    public static function storeProxy($hash, \Closure $make)
    {
        static::$proxyPool[$hash] = $make;
    }

    public static function removeProxy($hash)
    {
        unset(static::$proxyPool[$hash]);
    }

    public static function getProxy($hash, ...$args)
    {
        assert(isset(static::$proxyPool[$hash]));
        $val = static::$proxyPool[$hash];
        if ($val instanceof \Closure) {
            static::$proxyPool[$hash] = $val(...$args);
        }
        return static::$proxyPool[$hash];
    }

    public static function register($interface, \Closure $make)
    {
        static::$makePool[$interface] = $make;
    }

    public static function make($interface, ...$args)
    {
        assert(isset(static::$makePool[$interface]));
        $make = static::$makePool[$interface];
        return $make(...$args);
    }

    public static function makeOne($interface, ...$args)
    {
        if (!isset(static::$instancePool[$interface])) {
            static::$instancePool[$interface] = static::make($interface, ...$args);
        }
        return static::$instancePool[$interface];
    }

    public static function makeLazy($interface, ...$args)
    {
        return new ProxyClass(function() use($interface, $args) {
            return static::make($interface, ...$args);
        });
    }

    public static function makeLazyOne($interface, ...$args)
    {
        return new ProxyClass(function() use($interface, $args) {
            return static::makeOne($interface, ...$args);
        });
    }
}

final class ProxyClass
{
    public function __construct(\Closure $make)
    {
        CircleDepDemo::storeProxy(spl_object_hash($this), $make);
    }

    public function __call($name, $args)
    {
        $obj = CircleDepDemo::getProxy(spl_object_hash($this));
        return call_user_func_array([$obj, $name], $args);
    }

    public function __set($name, $value)
    {
        $obj = CircleDepDemo::getProxy(spl_object_hash($this));
        $obj->$name = $value;
    }

    public function __get($name)
    {
        $obj = CircleDepDemo::getProxy(spl_object_hash($this));
        return $obj->$name;
    }

    public function __destruct()
    {
        CircleDepDemo::removeProxy(spl_object_hash($this));
    }
}