<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/3/26
 * Time: 下午8:50
 */

namespace Minimalism\Test\A;

use function Minimalism\A\Core\isGeneratorFun;

require __DIR__ . "/../../vendor/autoload.php";


assert(isGeneratorFun(function() { yield; }));


function g() { yield; }
assert(isGeneratorFun(__NAMESPACE__ . "\\g"));


class c {
    public static function m1() { yield; }
    public function m2() { yield; }
}

assert(isGeneratorFun(__NAMESPACE__ . "\\c::m1"));
assert(isGeneratorFun([new c, "m2"]));


