<?php

namespace Minimalism\Test\Validation;

use Minimalism\Validation\All;

require __DIR__ . "/../../vendor/autoload.php";

// 1. 可使用All直接静态调用任意php内置返回bool值得方法
assert(All::is_int(1,2,3,4,5) === true);
assert(All::is_int(1,2,3,4,5, '6') === false);
assert(All::is_numeric(1,2,3,4,5, '6') === true);

assert(All::ctype_digit("1", "23", "445") === true);
assert(All::ctype_digit("1", "23", "a") === false);
assert(All::ctype_digit("1", "23", 5) === false);

// 2. 自定义方法 function($val) : bool;
$all_one2ten = All::make(function($val) {
    return in_array($val, range(1, 10), true);
});

assert($all_one2ten(1,2,3,11) === false);
assert($all_one2ten(1,2,3,10) === true);