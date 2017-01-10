<?php

namespace Minimalism\Test\Validation;

use Minimalism\Validation\Any;

require __DIR__ . "/../../vendor/autoload.php";

// 可使用All直接静态调用任意php内置返回bool值得方法
// Usage:: Any::is_*();

assert(Any::is_int('1','2','3','4','5') === false);
assert(Any::is_int('1','2','3','4',5) === true);
assert(Any::is_numeric('a','b',1) === true);

assert(Any::ctype_digit("a", "b", "445") === true);
assert(Any::ctype_digit("a", "b", "c") === false);
assert(Any::ctype_digit("a", "b", 5) === false);

// 2. 自定义方法 function($val) : bool;
$any_one2ten = Any::make(function($val) {
    return in_array($val, range(1, 10), true); 
});

assert($any_one2ten(14,13,12,11) === false);
assert($any_one2ten(14,13,12,10) === true);