<?php

namespace Minimalism\Validation;

/**
 * Class All
 *
 * @usage
 * 可使用All直接静态调用任意签名如 bool call($arg) 的方法
 *
 * Usage:: All::is_*();
 * All::is_int(1,2,3,4,5) === true
 * All::is_int(1,2,3,4,5, '6') === false
 * All::is_numeric(1,2,3,4,5, '6') === true
 *
 * 2. 自定义方法 function($val) : bool;
 * $all_one2ten = All::make(function($val) { return in_array($val, range(1, 10), true); });
 * $all_one2ten(1,2,3,11) === false
 * $all_one2ten(1,2,3,10) === true
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
 * 
 * ......
 */
class All
{
    /**
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
                if(!call_user_func($method, $arg)) {
                    return false;
                }
            }

            return true;  
        };
    }
}