<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/13
 * Time: 上午12:17
 */

namespace Minimalism\Scheme\Value;


use Minimalism\Scheme\Interpreter;

abstract class Value
{
    /* @var $VOID VoidValue */
    public static $VOID;
    /* @var $ANY AnyType */
    public static $ANY;

    abstract public function __toString();

    public static function from($phpVar)
    {
        $type = gettype($phpVar);
        switch ($type) {
            case "boolean":
                return new BoolValue($phpVar);
            case "integer":
                return new IntValue($phpVar);
            case "double":
                return new FloatValue($phpVar);
            case "string":
                return new StringValue($phpVar);
            case "NULL":
                return Value::$VOID;
            case "resource":
                return new ResourceValue($phpVar);
                break;


            // TODO
            case "array":
                break;
            case "object":
                break;

            case "unknown type":
            default:
                Interpreter::abort("unsupport type");
        }
    }
}