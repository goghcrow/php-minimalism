<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/14
 * Time: 下午4:30
 */

namespace Minimalism\Scheme\Value\Primitives;


use Minimalism\Scheme\Ast\Node;
use Minimalism\Scheme\Interpreter;
use Minimalism\Scheme\Scope;
use Minimalism\Scheme\Value\BoolType;
use Minimalism\Scheme\Value\BoolValue;
use Minimalism\Scheme\Value\PrimFun;
use Minimalism\Scheme\Value\Type;
use Minimalism\Scheme\Value\Value;

/**
 * And is keyword, so AndPrimFun
 * Class AndPrimFun
 * @package Minimalism\Scheme\Value\Primitives
 */
class And_ extends PrimFun
{
    public function __construct()
    {
        parent::__construct("and", 2);
    }

    /**
     * @param Value[] $args
     * @param Node $location
     * @return BoolValue
     */
    public function apply(array $args, Node $location)
    {
        list($v1, $v2) = $args;

        if ($v1 instanceof BoolValue && $v2 instanceof BoolValue) {
            return new BoolValue($v1->value and $v2->value);
        }

        Interpreter::abort("incorrect argument types for and: $v1, $v2", $location);
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

        if ($v1 instanceof BoolType && $v2 instanceof BoolType) {
            return Type::$BOOL;
        }

        Interpreter::abort("incorrect argument types for and: $v1, $v2", $location);
        return null;
    }
}