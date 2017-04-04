<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/3/26
 * Time: 下午8:50
 */

namespace Minimalism\Test\A;

use function Minimalism\A\Core\isGenFun;

require __DIR__ . "/../../vendor/autoload.php";


assert(isGenFun(function() { yield; }));


function g() { yield; }
assert(isGenFun(__NAMESPACE__ . "\\g"));


class c {
    public static function m1() { yield; }
    public function m2() { yield; }
}

assert(isGenFun(__NAMESPACE__ . "\\c::m1"));
assert(isGenFun([new c, "m2"]));


