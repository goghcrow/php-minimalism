<?php

namespace Minimalism\TimeTravel;

// echo extension=runkit.so >> php.ini
// echo runkit.internal_override=1 >> php.ini


if (extension_loaded("runkit") === false) {
    throw new \RuntimeException("runkit is needed");
}
ini_set("runkit.internal_override", 1);


function time_travel($seconds)
{
    $GLOBALS["__timeTravel"] = $seconds;

    // replace time()
    runkit_function_rename ("time", "original_time");
    runkit_function_add("time", "", <<<'FUNC'
        return original_time() + $GLOBALS["__timeTravel"];
FUNC
    );

    // replace strtotime()
    runkit_function_rename ("strtotime", "original_strtotime");
    runkit_function_add("strtotime", '$time, $now = 0', <<<'FUNC'
        if (isset($now)
                && !empty($now))
            return original_strtotime($time, $now);
        else
            return original_strtotime($time, time());
FUNC
    );

    // replace date()
    runkit_function_rename ("date", "original_date");
    runkit_function_add("date", '$format, $timestamp = 0', <<<'FUNC'
        if (isset($timestamp)
                && !empty($timestamp))
            return original_date($format, $timestamp);
        else
            return original_date($format, time());
FUNC
    );

    return true;
}