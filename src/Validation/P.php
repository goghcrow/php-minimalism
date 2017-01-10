<?php

namespace Minimalism\Validation;

use Minimalism\Validation\Predicate\PArray;
use Minimalism\Validation\Predicate\PInt;
use Minimalism\Validation\Predicate\PMixed;
use Minimalism\Validation\Predicate\PNil;
use Minimalism\Validation\Predicate\PString;
use Minimalism\Validation\VType\VNil;


/**
 * Class Predicate
 *
 * 生成Predicate的辅助类
 */
class P
{
    /**
     * @var VNil
     */
    protected static $nil;

    /**
     * @return VNil
     */
    public static function ofNil() {
        if (static::$nil === null) {
            static::$nil = PNil::of(null);
        }
        return static::$nil;
    }

    /**
     * @param callable $predicate
     * @return PInt
     */
    public static function ofInt($predicate = null) {
        return PInt::of($predicate);
    }

    /**
     * @param null $predicate
     * @return PString
     */
    public static function ofString($predicate = null) {
        return PString::of($predicate);
    }

    /**
     * @param null $predicate
     * @return PArray
     */
    public static function ofArray($predicate = null) {
        return PArray::of($predicate);
    }

    /**
     * @return PInt
     */
    public static function strlen() {
        return PString::of()->len();
    }

    /**
     * @return PInt
     */
    public static function count() {
        return PArray::of()->count();
    }

    /**
     * @return PMixed
     */
    public static function isEmpty() {
        return PMixed::of()->isEmpty();
    }

    /**
     * @return PMixed
     */
    public static function notEmpty() {
        return PMixed::of()->notEmpty();
    }
}