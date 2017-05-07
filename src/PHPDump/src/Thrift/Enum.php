<?php

namespace Minimalism\PHPDump\Thrift;


abstract class Enum
{
    private static $classMap;

    public static function getName($type)
    {
        $class = static::class;

        if (!isset(self::$classMap[$class])) {
            $clazz = new \ReflectionClass($class);
            self::$classMap[$class] = array_flip($clazz->getConstants());
        }

        $consts = self::$classMap[$class];
        if (isset($consts[$type])) {
            return $consts[$type];
        } else {
            return "Unknown";
        }
    }
}