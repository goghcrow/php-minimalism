<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/13
 * Time: 上午12:35
 */

namespace Minimalism\Scheme\Value;


final class Type
{
    /* @var BoolType */
    public static $BOOL;
    /* @var IntType */
    public static $INT;
    /* @var FloatType */
    public static $FLOAT; // TODO ???
    /* @var StringType */
    public static $STRING;

    /**
     * @param Value $type1
     * @param Value $type2
     * @param bool $ret
     * @return false
     */
    public static function subtype(Value $type1, Value $type2, $ret)
    {
        if (!$ret && $type2 instanceof AnyType) {
            return true;
        }

        if ($type1 instanceof UnionType) {
            foreach ($type1->values as $t) {
                if (!self::subtype($t, $type2, false)) {
                    return false;
                }
            }
            return true;
        } else if ($type2 instanceof UnionType) {
            return $type2->contains($type1);
        } else {
            // TODO
            // return get_class($type1) === get_class($type2);
            return $type1 === $type2;
        }
    }
}