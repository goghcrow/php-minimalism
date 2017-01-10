<?php

if(!function_exists("error_clear_last")) {
    // PHP7　only
    function error_clear_last() {
        set_error_handler(function(){}, 0);
        @trigger_error("");
        restore_error_handler();
    }
}

// auto clear
function error_get_last_msg($simple = false) {
    $err = error_get_last();
    $hasErr = $err !== null && $err["message"] !== "";
    if($hasErr) {
        error_clear_last();
        return  $simple ? $err["message"] : print_r($err, true);
    } else {
        return "";
    }
}

// 清除错误测试
// 无自定义错误处理函数情况下
/*
error_reporting(E_ALL);
@trigger_error("");
$err = error_get_last();
// $iserr = $err !== null && $err["message"] !== "";
assert($err["message"] === "");

error_reporting(E_ALL);
@$__undefVar;
$err = error_get_last();
// $iserr = $err !== null && $err["message"] !== "Undefined variable: __undefVar";
assert($err["message"] === "Undefined variable: __undefVar");
*/

// 自定义错误处理函数,error_get_last返回null
/*
error_reporting(E_ALL);
set_error_handler(function() {
    // var_dump(func_get_args());
});
@trigger_error("");
$err = error_get_last();
assert($err === null);

error_reporting(E_ALL);
set_error_handler(function() {
    // var_dump(func_get_args());
});
@$__undefVar;
$err = error_get_last();
assert($err === null);
*/

// 临时替换错误处理函数的hacker方式比较靠谱
/*
error_reporting(E_ALL);
// var_dump or anything else, as this will never be called because of the 0
set_error_handler("var_dump", 0);
@$__undefVar;
restore_error_handler();
$err = error_get_last();
assert($err["message"] === "Undefined variable: __undefVar");

error_reporting(E_ALL);
set_error_handler("var_dump", 0);
@trigger_error("");
restore_error_handler();
$err = error_get_last();
// $iserr = $err !== null && $err["message"] !== "";
assert($err["message"] === "");
*/

// 参考 http://php.net/manual/en/function.error-get-last.php
