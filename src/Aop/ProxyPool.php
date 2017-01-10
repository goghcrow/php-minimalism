<?php

namespace Minimalism\Aop;

use SplObjectStorage;

/**
 * Class ProxyPool
 * @package Minimalism\Aop
 *
 * proxyObject => aop
 */
class ProxyPool extends SplObjectStorage
{
    protected static function getPool()
    {
        static $pool;
        if ($pool === null) {
            $pool = new ProxyPool();
        }
        return $pool;
    }

    public static function setAop(ProxyClass $object, Aop $aop)
    {
        static::getPool()->attach($object, $aop);
    }

    /**
     * @param ProxyClass $object
     * @return Aop
     */
    public static function getAop(ProxyClass $object)
    {
        return static::getPool()->offsetGet($object);
    }
}