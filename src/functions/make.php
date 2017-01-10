<?php

namespace Minimalism\functions;

use ReflectionClass;

/**
 * @param string $class
 * @param array $args
 * @return object
 */
function make($class, array $args = [])
{
    static $isGt56;
    if ($isGt56 === null) {
        $isGt56 = version_compare(phpversion(), '5.6.0', '>=');
    }

    if($isGt56){
        // magic, nomatter performance
        $instance = new $class(eval('...') . $args);
    } else {
        $reflect  = new ReflectionClass($class);
        $instance = $reflect->newInstanceArgs($args);
    }
    return $instance;
}