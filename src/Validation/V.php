<?php

namespace Minimalism\Validation;

use Minimalism\Validation\VType\VArray;
use Minimalism\Validation\VType\VBool;
use Minimalism\Validation\VType\VCallable;
use Minimalism\Validation\VType\VFloat;
use Minimalism\Validation\VType\VInt;
use Minimalism\Validation\VType\VIp;
use Minimalism\Validation\VType\VMac;
use Minimalism\Validation\VType\VMixed;
use Minimalism\Validation\VType\VNil;
use Minimalism\Validation\VType\VObject;
use Minimalism\Validation\VType\VString;
use Minimalism\Validation\VType\VUrl;

/**
 * Class V
 * @DateTime 2016-07-10 21:57
 *
 * 一些说明:
 * 1. 针对对empty等参数验证方式的争论, 写的强类型的变量处理库~
 * 2. 含义 V: variable or validate or Value
 * 3. 借鉴了一些Monad的思路, 出于简单考虑,木有按照惰性求值的思路来实现
 * 4. 验证在of[Type]()方法内保证, 所有自类型, 排除VMixed, $this->var都只可能是V{$Type}类型
 * 5. filter 的所有opts 均可以 bitwise-OR 方式组合
 */
class V 
{
    /**
     * @param $var
     * @return VMixed
     */
    public static function of($var) {
        return VMixed::of($var);
    }

    /**
     * @param string $reason
     * @return VNil
     */
    public static function ofNil($reason = null) {
        return VNil::of($reason);
    }

    /**
     * @param $var
     * @param $opts
     * @return VInt
     *
     * ofInt($var, FILTER_FLAG_ALLOW_OCTAL)
     * ofInt($var, FILTER_FLAG_ALLOW_HEX)
     * ofInt($var, FILTER_FLAG_ALLOW_OCTAL | FILTER_FLAG_ALLOW_HEX)
     *
     */
    public static function ofInt($var, $opts = null) {
        $var = filter_var($var, FILTER_VALIDATE_INT, $opts);
        if ($var === false) {
            return static::ofNil("Invalid Int");
        }
        return VInt::of($var);
    }

    /**
     * @param $var
     * @param $opts
     * @return VFloat
     * ofFloat($var, FILTER_FLAG_ALLOW_THOUSAND)
     */
    public static function ofFloat($var, $opts = null) {
        $var = filter_var($var, FILTER_VALIDATE_FLOAT, $opts);
        if ($var === false) {
            return static::ofNil("Invalid Float");
        }
        return VFloat::of($var);
    }

    /**
     * @param $var
     * @return VString
     * FILTER_FLAG_STRIP_LOW, FILTER_FLAG_STRIP_HIGH, FILTER_FLAG_STRIP_BACKTICK, FILTER_FLAG_ENCODE_LOW, FILTER_FLAG_ENCODE_HIGH, FILTER_FLAG_ENCODE_AMP
     */
    public static function ofString($var) {
        $var = filter_var($var, FILTER_UNSAFE_RAW);
        if ($var === false) {
            return static::ofNil("Invalid String");
        }
        return VString::of($var);
    }

    /**
     * @param $var
     * @return Varray
     */
    public static function ofArray($var) {
        if (!is_array($var)) {
            return static::ofNil("Invalid Array");
        }
        reset($var);
        return VArray::of($var);
    }

    /**
     * @param $var
     * @return VObject
     */
    public static function ofObject($var) {
        if (!is_object($var)) {
            return static::ofNil("Invalid Object");
        }
        return VObject::of($var);
    }

    /**
     * @param $var
     * @return VBool
     */
    public static function ofBool($var) {
        $var = filter_var($var, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($var === null) {
            return static::ofNil("Invalid Bool");
        }
        return VBool::of($var);
    }

    /**
     * @param $callable
     * @return VNil|VCallable
     */
    public static function ofCallable($callable) {
        if (!is_callable($callable)) {
            return static::ofNil("Invalid Callable");
        }
        return VCallable::of($callable);
    }

    /**
     * @param $var
     * @return VString
     */
    public static function ofEmail($var) {
        $var = filter_var($var, FILTER_VALIDATE_EMAIL);
        if ($var === false) {
            return static::ofNil("Invalid Email");
        }
        return VString::of($var);
    }

    /**
     * @param $var
     * @param $opts
     * @return VIp
     * FILTER_FLAG_IPV4, FILTER_FLAG_IPV6, FILTER_FLAG_NO_PRIV_RANGE, FILTER_FLAG_NO_RES_RANGE
     */
    public static function ofIp($var, $opts = null) {
        $var = filter_var($var, FILTER_VALIDATE_IP, $opts);
        if ($var === false) {
            return static::ofNil("Invalid Ip");
        }
        return VIp::of($var);
    }

    /**
     * @param $var
     * @return VMac
     */
    public static function ofMac($var) {
        $var = filter_var($var, FILTER_VALIDATE_MAC);
        if ($var === false) {
            return static::ofNil("Invalid Mac Address");
        }
        return VMac::of($var);
    }

    /**
     * @param $var
     * @param $opts
     * @return Vurl
     * FILTER_FLAG_SCHEME_REQUIRED, FILTER_FLAG_HOST_REQUIRED, FILTER_FLAG_PATH_REQUIRED, FILTER_FLAG_QUERY_REQUIRED
     */
    public static function ofUrl($var, $opts = null) {
        $var = filter_var($var, FILTER_VALIDATE_URL, $opts);
        if ($var === false) {
            return static::ofNil("Invalid Url");
        }
        return VUrl::of($var);
    }

    /**
     * @param $var
     * @param $regex
     * @return VString
     */
    public static function fromRegex($var, $regex) {
        $var = filter_var($var, FILTER_VALIDATE_REGEXP, ["options" => ["regexp"=>$regex]]);
        if ($var === false) {
            return static::ofNil("Invalid Regex");
        }
        return VString::of($var);
    }

    /**
     * @param $var
     * @param $callable
     * @return VMixed
     */
    public static function fromCallback($var, $callable) {
        $var = filter_var($var, FILTER_CALLBACK, ["options" => $callable]);
        if ($var === false) {
            return static::ofNil("Valid Fail In Predicate");
        }
        return VMixed::of($var);
    }

    // TODO 加载验证失败抛出提示语,跑
    // public static function loadExTable(array $conf) {}
}