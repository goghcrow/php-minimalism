<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/11
 * Time: 下午9:46
 */

/*
exp ::= variable                     [references]
      |  exp(exp)                    [function application]
      |  function (variable) exp     [anonymous functions]
      |  (exp)                       [precedence]

*/

class Church
{
    /** @var callable */
    public static $void;

    /**
     * Church Booleans true
     *
     * @var callable
     */
    public static $true;

    /**
     * Church Booleans false
     *
     * @var callable
     */
    public static $false;

    /** @var callable */
    public static $if;

    public static function boolify($churchBoolean)
    {
        $curry = $churchBoolean(function($_) {
            return true;
        });
        return $curry(function($_) {
            return false;
        });
    }

}

Church::$void = function($x) { return $x; };

Church::$true = function($onTrue) {
    return function($onFalse) use($onTrue) {
        return $onTrue(Church::$void);
    };
};
Church::$false = function($onTrue) {
    return function($onFalse) {
        return $onFalse(Church::$void);
    };
};
Church::$if = function($test) {
    return function($onTrue) use($test) {
        return function($onFalse) use($test, $onTrue) {
            $curry = $test($onTrue);
            return $curry($onFalse);
        };
    };
};

// TODO Church类反射extract到当前符号表
// extract();

$if = Church::$if;


$c = $if(Church::$true);
$c = $c(function($_) {
    return true;
});
$r = $c(function($_) {
    return false;
});
var_dump($r);