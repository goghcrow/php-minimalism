<?php

namespace Minimalism\Validation;

/**
 * Class Any
 *
 * @usage
 * 可使用Any直接静态调用任意签名如 bool call($arg) 的方法
 * 
 * Any::is_int('1','2','3','4','5') === false
 * Any::is_int('1','2','3','4',5) === true
 * Any::is_numberic('a','b',1) === true
 *
 * 2. 自定义方法 function($val) : bool;
 * $any_one2ten = Any::make(function($val) { return in_array($val, range(1, 10), true); });
 * $any_one2ten(14,13,12,11) === false
 * $any_one2ten(14,13,12,10) === true
 *
 * @method static bool is_null(...$args)
 * @method static bool is_resource(...$args)
 * @method static bool is_bool(...$args)
 * @method static bool is_long(...$args)
 * @method static bool is_float(...$args)
 * @method static bool is_int(...$args)
 * @method static bool is_integer(...$args)
 * @method static bool is_double(...$args)
 * @method static bool is_real(...$args)
 * @method static bool is_numeric(...$args)
 * @method static bool is_string(...$args)
 * @method static bool is_array(...$args)
 * @method static bool is_object(...$args)
 * @method static bool is_scalar(...$args)
 * @method static bool is_callable(...$args)
 *
 * @method static bool ctype_alnum(...$args)
 * @method static bool ctype_alpha(...$args)
 * @method static bool ctype_cntrl(...$args)
 * @method static bool ctype_digit(...$args)
 * @method static bool ctype_lower(...$args)
 * @method static bool ctype_graph(...$args)
 * @method static bool ctype_print(...$args)
 * @method static bool ctype_punct(...$args)
 * @method static bool ctype_space(...$args)
 * @method static bool ctype_upper(...$args)
 * @method static bool ctype_xdigit(...$args)
 * ......
 */
class Any
{
    /**
     * 静态调用
     * @param mixed $method
     * @param array $args
     * @return bool
     */
    public static function __callStatic($method, $args) {
        return call_user_func_array(static::make($method), $args);
    }

    public static function make($method) {
        return function(/* ...$args */) use($method) {
            $args = func_get_args();

            if(empty($args)) {
                return false;
            }

            foreach($args as $arg) {
                if(call_user_func($method, $arg)) {
                    return true;
                }
            }

            return false;
        };
    }
}