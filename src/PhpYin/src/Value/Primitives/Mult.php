<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/14
 * Time: 下午4:49
 */

namespace Minimalism\Scheme\Value\Primitives;



use Minimalism\Scheme\Ast\Node;
use Minimalism\Scheme\Interpreter;
use Minimalism\Scheme\Scope;
use Minimalism\Scheme\Value\FloatType;
use Minimalism\Scheme\Value\FloatValue;
use Minimalism\Scheme\Value\IntType;
use Minimalism\Scheme\Value\IntValue;
use Minimalism\Scheme\Value\PrimFun;
use Minimalism\Scheme\Value\Type;
use Minimalism\Scheme\Value\Value;

class Mult extends PrimFun
{
    public function __construct()
    {
        parent::__construct("*", 2);
    }

    /**
     * @param Value[] $args Values
     * @param Node $location
     * @return FloatValue|IntValue
     */
    public function apply(array $args, Node $location)
    {
        list($v1, $v2) = $args;

        if ($v1 instanceof IntValue && $v2 instanceof IntValue) {
            return new IntValue($v1->value * $v2->value);
        }
        if ($v1 instanceof FloatValue && $v2 instanceof FloatValue) {
            return new FloatValue($v1->value * $v2->value);
        }
        if ($v1 instanceof FloatValue && $v2 instanceof IntValue) {
            return new FloatValue($v1->value * $v2->value);
        }
        if ($v1 instanceof IntValue && $v2 instanceof FloatValue) {
            return new FloatValue($v1->value * $v2->value);
        }

        Interpreter::abort("incorrect argument types for *: $v1, $v2", $location);
        return null;
    }

    /**
     * @param Value[] $args Types
     * @param Node $location
     * @return IntType|FloatType
     */
    public function typecheck(array $args, Node $location)
    {
        list($v1, $v2) = $args;

        if ($v1 instanceof FloatType || $v2 instanceof FloatType) {
            return Type::$FLOAT;
        }
        if ($v1 instanceof IntType && $v2 instanceof IntType) {
            return Type::$INT;
        }

        Interpreter::abort("incorrect argument types for *: $v1, $v2", $location);
        return null;
    }
}