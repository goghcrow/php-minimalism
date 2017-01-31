<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/14
 * Time: 下午4:43
 */

namespace Minimalism\Scheme\Value\Primitives;



use Minimalism\Scheme\Ast\Node;
use Minimalism\Scheme\Ast\Str;
use Minimalism\Scheme\Interpreter;
use Minimalism\Scheme\Scope;
use Minimalism\Scheme\Value\BoolType;
use Minimalism\Scheme\Value\BoolValue;
use Minimalism\Scheme\Value\FloatValue;
use Minimalism\Scheme\Value\IntType;
use Minimalism\Scheme\Value\IntValue;
use Minimalism\Scheme\Value\PrimFun;
use Minimalism\Scheme\Value\StringValue;
use Minimalism\Scheme\Value\Type;
use Minimalism\Scheme\Value\Value;

class Eq extends PrimFun
{

    public function __construct()
    {
        parent::__construct("=", 2);
    }

    /**
     * @param Value[] $args
     * @param Node $location
     * @return BoolValue
     */
    public function apply(array $args, Node $location)
    {
        list($v1, $v2) = $args;

        if ($v1 instanceof IntValue && $v2 instanceof IntValue) {
            return new BoolValue($v1->value === $v2->value);
        }
        if ($v1 instanceof FloatValue && $v2 instanceof FloatValue) {
            return new BoolValue($v1->value === $v2->value);
        }
        if ($v1 instanceof FloatValue && $v2 instanceof IntValue) {
            return new BoolValue($v1->value === $v2->value);
        }
        if ($v1 instanceof IntValue && $v2 instanceof FloatValue) {
            return new BoolValue($v1->value === $v2->value);
        }




        if ($v1 instanceof StringValue && $v2 instanceof StringValue) {
            return new BoolValue($v1->value === $v2->value);
        }
        if ($v1 instanceof BoolValue && $v2 instanceof BoolValue) {
            return new BoolValue($v1->value === $v2->value);
        }


        if ($v1 === Value::$VOID || $v2 === Value::$VOID) {
            return new BoolValue($v1 === $v2);
        }


        Interpreter::abort("incorrect argument types for =: $v2, $v2", $location);
        return null;
    }

    /**
     * @param Value[] $args
     * @param Node $location
     * @return BoolType
     */
    public function typecheck(array $args, Node $location)
    {
        list($v1, $v2) = $args;

        if (!($v1 instanceof IntType || $v1 instanceof FloatValue) ||
            !($v2 instanceof IntType || $v2 instanceof FloatValue))
        {
            Interpreter::abort("incorrect argument types for =: $v2, $v2", $location);
        }

        return Type::$BOOL;
    }
}