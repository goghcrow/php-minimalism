<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/14
 * Time: 下午4:50
 */

namespace Minimalism\Scheme\Value\Primitives;


use Minimalism\Scheme\Ast\Node;
use Minimalism\Scheme\Value\PrimFun;
use Minimalism\Scheme\Value\UnionType;
use Minimalism\Scheme\Value\Value;

class U extends PrimFun
{
    public function __construct($name, $arity)
    {
        parent::__construct("U", -1); // arity 不定
    }

    /**
     * @param Value[] $args Values
     * @param Node $location
     * @return Value
     */
    public function apply(array $args, Node $location)
    {
        return null;
    }

    /**
     * @param Value[] $args Types
     * @param Node $location
     * @return UnionType|Value
     */
    public function typecheck(array $args, Node $location)
    {
        return UnionType::union($args);
    }
}