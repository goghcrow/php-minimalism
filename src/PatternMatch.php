<?php

namespace Minimalism;

use ReflectionMethod;
use ReflectionParameter;


/**
 * Class PatternMatch
 *
 * TODO:
 * ReflectionParameter::isPassedByReference
 * ReflectionParameter::getType
 * ReflectionParameter::isVariadic
 */
class PatternMatch
{
    private $object;

    /**
     * @var array
     * methodName => list(ReflectionMethod, ReflectionParameter[])
     */
    private $methods = [];

    /**
     * @var ReflectionMethod
     */
    private $defaultMethod;

    public static function make($object, $methodName)
    {
        static $cache = [];

        if (is_object($object)) {
            $cacheKey = spl_object_hash($object) . ":$methodName";
        } else {
            $cacheKey = "$object:$methodName";
        }
        if (!isset($cache[$cacheKey])) {
            $cache[$cacheKey] = new static($object, $methodName);
        }

        return $cache[$cacheKey];
    }

    private function __construct($object, $methodName)
    {
        $this->object = $object;
        $this->scanMethod($methodName);
    }

    private function scanMethod($methodName)
    {
        $clazz = new \ReflectionClass($this->object);
        $flag = \ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_PROTECTED | \ReflectionMethod::IS_PRIVATE;

        $methodSignatures = [];

        $prefix = "{$methodName}_";
        $prefix_len = strlen($prefix);

        foreach ($clazz->getMethods($flag) as $method) {
            $name = $method->getName();
            if (substr($name, 0, $prefix_len) !== $prefix) {
                continue;
            }

            $methodSignatures[$name] = [$method, $method->getParameters()];
        }

        $this->methods = $methodSignatures;
        $this->defaultMethod = $clazz->getMethod($prefix);
    }

    private function matchMethod(array $args)
    {
        foreach ($this->methods as $name => list($method, $params)) {
            if (count($args) !== count($params)) {
                continue;
            }

            /* @var ReflectionParameter $param */
            foreach ($params as $i => $param) {
                if (!$this->matchParameter($param, $args[$i])) {
                    continue 2;
                }
            }
            return $method;
        }

        return $this->defaultMethod;
    }

    private function matchParameter(ReflectionParameter $param, $arg)
    {
        if ($param->isDefaultValueAvailable()) {
            if ($param->getDefaultValue() !== $arg) {
                return false;
            }
        } else {
            if ($param->isArray()) {
                if (!is_array($arg)) {
                    return false;
                }
            } else if ($param->isCallable()) {
                if (!is_callable($arg)) {
                    return false;
                }
            } else if ($clazz = $param->getClass()) {
                if (!is_object($arg)) {
                    return false;
                }
                if (!$clazz->isInstance($arg)) {
                    return false;
                }
            }
        }

        return true;
    }

    public function __invoke(...$args)
    {
        $object = is_object($this->object) ? $this->object : null;
        $method = $this->matchMethod($args);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $args);
    }
}