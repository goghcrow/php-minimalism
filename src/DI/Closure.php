<?php

namespace Minimalism\DI;

/**
 * Class Closure
 * @package Minimalism
 *
 * https://github.com/tpunt/PHP7-Reference/blob/master/php71-reference.md#convert-callables-to-closures-with-closurefromcallable
 * 发现 php7.1 加入了一个 之前自己实现的方法 callable -> Closure (ˇˍˇ)
 * TODO: 翻一下php 7.1 源码, 看一下怎么实现的
 */
final class Closure
{
    /**
     * convert callable to Closure
     * @param callable $callable
     * @throws \ReflectionException
     * @return \Closure
     */
    public static function fromCallable(callable $callable)
    {
        if (is_object($callable)) {
            return self::getObjectCallableClosure($callable);
        }
        if (is_string($callable)) {
            return self::getStringCallableClosure($callable);
        }
        if (is_array($callable)) {
            return self::getArrayCallableClosure($callable);
        }

        throw new ClosureException("Can not get closure from type of " . gettype($callable));
    }

    /**
     * 获取可调用对象的闭包
     * 可调用对象:
     * 1. \Closure
     * 2. 实现了__invoke魔术方法的类实例
     * (is_callable(class with method __invoke) === true)
     *
     * @param $callable
     * @return \Closure
     */
    private static function getObjectCallableClosure($callable)
    {
        if ($callable instanceof \Closure) {
            return $callable;
        }

        $method = new \ReflectionMethod($callable, "__invoke");
        return $method->getClosure($callable);
    }

    /**
     * 获取可调用字符串的闭包
     * 可调用字符串
     * 1. class::method | static::method
     * 2. function name
     *
     * @param string $callable
     * @return \Closure
     */
    private static function getStringCallableClosure($callable)
    {
        if (strpos($callable, "::") === false) {
            $function = new \ReflectionFunction($callable);
            return $function->getClosure();
        }

        list($clazz, $method) = explode("::", $callable);
        if ($clazz === "static") {
            throw new ClosureException("Does Not Implement");
        }

        $method = new \ReflectionMethod($clazz, $method);
        return $method->getClosure();
    }

    /**
     * 获取可调用数组的闭包
     * 可调用数组:
     * 1. ["class", "[self|parent|static::]method"]
     * 2. [object, "[self|parent|static::]method"]
     *
     * @param array $callable
     * @return \Closure
     */
    private static function getArrayCallableClosure(array $callable)
    {
        list($clazz, $method) = $callable;

        if(strpos($method, "::") === false) {
            $method = new \ReflectionMethod($clazz, $method);
            return $method->getClosure(is_object($clazz) ? $clazz : null);
        }

        list($clazzScope, $method) = explode("::", $method);

        if ($clazzScope === "self") {
            $method = new \ReflectionMethod($clazz, $method);
            return $method->getClosure(is_object($clazz) ? $clazz : null);
        }

        if ($clazzScope === "parent") {
            if (is_object($clazz)) {
                $subClazz = new \ReflectionClass($clazz);
                $parentClazz = $subClazz->getParentClass();
                $parentMethod = $parentClazz->getMethod($method);
                return $parentMethod->getClosure($clazz); // non-static method ===> non-static Closure
            } else if (is_string($clazz)) {
                $clazz = get_parent_class($clazz);
                $method = new \ReflectionMethod($clazz, $method);
                return $method->getClosure(null); // static method ==> static Closure
            }
        }

        if ($clazzScope === "static") {
            throw new ClosureException("Dose Not Implement");
        }

        throw new ClosureException("Can not get closure from the array callable");
    }
}

class ClosureException extends \RuntimeException {}