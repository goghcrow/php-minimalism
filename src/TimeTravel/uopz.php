<?php

namespace Minimalism\TimeTravel;

// echo extension=uopz.so >> php.ini
// echo uopz.overloads=1 >> php.ini

if (extension_loaded("uopz") === false) {
    throw new \RuntimeException("runkit is needed");
}
ini_set("uopz.overloads", 1);

function time_travel($seconds)
{
    // replace time()
    $ori_time = "\0time";
    assert(!function_exists($ori_time));
    uopz_rename("time", $ori_time);
    uopz_function("time", function() use($seconds, $ori_time) {
        return $ori_time() + $seconds;
    });

    // replace strtotime()
    $ori_strtotime = "\0strtotime";
    assert(!function_exists($ori_strtotime));
    uopz_rename("strtotime", $ori_strtotime);
    uopz_function("strtotime", function($time, $now = 0) use($ori_strtotime) {
        if (isset($now) && !empty($now)) {
            return $ori_strtotime($time, $now);
        } else {
            return $ori_strtotime($time, time());
        }
    });

    // replace date()
    $ori_date = "\0date";
    uopz_rename("date", $ori_date);
    uopz_function("date", function($format, $timestamp = 0) use($ori_date) {
        if (isset($timestamp) && !empty($timestamp)) {
            return $ori_date($format, $timestamp);
        } else {
            return $ori_date($format, time());
        }
    });
}