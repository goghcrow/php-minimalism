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
use Minimalism\Scheme\Value\BoolType;
use Minimalism\Scheme\Value\BoolValue;
use Minimalism\Scheme\Value\PrimFun;
use Minimalism\Scheme\Value\Type;
use Minimalism\Scheme\Value\Value;

class Not extends PrimFun
{
    public function __construct()
    {
        parent::__construct("not", 1);
    }

    /**
     * @param Value[] $args Values
     * @param Node $location
     * @return BoolValue
     */
    public function apply(array $args, Node $location)
    {
        $v1 = $args[0];

        if ($v1 instanceof BoolValue) {
            return new BoolValue(!$v1->value);
        }
        Interpreter::abort("incorrect argument type for not: $v1", $location);
        return null;

    }

    /**
     * @param Value[] $args Types
     * @param Node $location
     * @return BoolType
     */
    public function typecheck(array $args, Node $location)
    {
        $v1 = $args[0];

        if ($v1 instanceof BoolType) {
            return Type::$BOOL;
        }
        Interpreter::abort("incorrect argument type for not: v1", $location);
        return null;
    }
}