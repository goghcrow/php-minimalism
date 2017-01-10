<?php

namespace Minimalism;


/**
 * Class ObjectPool
 * fixed size object pool
 */
class ObjectPool
{
    /**
     * @var \SplObjectStorage[]
     * k object
     * v objectTemple
     */
    private static $pool;

    /**
     * @var \SplObjectStorage[]
     */
    private static $inUse;

    public static function create($object, $size)
    {
        if (!is_object($object)) {
            throw new \InvalidArgumentException("must be object");
        }

        $class = get_class($object);
        if (isset(static::$pool[$class])) {
            return;
        }

        static::$pool[$class] = [];
        static::$inUse[$class] = [];

        $size = max(1, $size);
        static::$pool[$class][0] = [$object, $size];

        for ($i = 1; $i < $size + 1; $i++) {
            static::$pool[$class][] = clone $object;
        }
    }

    public static function release($object)
    {
        if (!is_object($object)) {
            throw new \InvalidArgumentException("must be object");
        }

        $class = get_class($object);
        if (!isset(static::$pool[$class])) {
            throw new \InvalidArgumentException("$class not in pool");
        }

        $hash = spl_object_hash($object);
        if (!isset(static::$inUse[$class][$hash])) {
            return;
        }
        unset(static::$inUse[$class][$hash]);

        $maxSize = static::$pool[$class][0][1];
        if (count(static::$pool[$class]) - 1 < $maxSize) {
            static::$pool[$class][$hash] = $object;
        } else {
            unset($object);
        }
        return;
    }

    public static function get($class)
    {
        if (!isset(static::$pool[$class]) || !isset(static::$pool[$class][0])) {
            throw new \InvalidArgumentException("$class not in pool");
        }

        if (count(static::$pool[$class]) > 1) {
            /** @noinspection PhpParamsInspection */
            $object = array_pop(static::$pool[$class]);
        } else {
            $object = clone static::$pool[$class][0][0];
        }

        static::$inUse[$class][spl_object_hash($object)] = $object;
        return $object;
    }
}