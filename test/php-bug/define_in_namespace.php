<?php

namespace bar;

// If the parameter case_insensitive is set to true
// produces notice - Constant null already defined

// 把当前ns的null重定义为true, 不能使用define的第三个参数忽略大小写敏感
define(__NAMESPACE__ . "\\null", true);
define(__NAMESPACE__ . "\\NULL", true);

// 把当前ns的true重定义为false, 不能使用define的第三个参数忽略大小写敏感
define(__NAMESPACE__ . "\\true", false);
define(__NAMESPACE__ . "\\TRUE", false);

// true 已经被定义成false了,不能把false再定义成true, 否则还是false, 这里将int转型为bool
define(__NAMESPACE__ . "\\false", boolval(1));
define(__NAMESPACE__ . "\\FALSE", boolval(1));

//define(__NAMESPACE__ . "\\null", (bool)1, true);
//define(__NAMESPACE__ . "\\true", (bool)0, true);
//define(__NAMESPACE__ . "\\false", (bool)1, true);

define("T", (bool)1);
define("F", (bool)0);

assert(NULL === T);
assert(null === T);
assert(false === T);
assert(FALSE === T);
assert(TRUE === F);
assert(true === F);

//const NULL = 0; // fatal error;
//const true = 'stupid'; // also fatal error;