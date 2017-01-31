<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/13
 * Time: 上午1:49
 */

namespace Minimalism\Scheme\Value;


use Minimalism\Scheme\Ast\Node;

abstract class PrimFun extends Value
{
    public $name;
    public $arity;

    public function __construct($name, $arity)
    {
        $this->name = $name;
        $this->arity = $arity;
    }

    /**
     * @param Value[] $args Values
     * @param Node $location
     * @return Value
     */
    public abstract function apply(array $args, Node $location);

    /**
     * @param Value[] $args Types
     * @param Node $location
     * @return Value
     */
    public abstract function typecheck(array $args, Node $location);

    public function __toString()
    {
        return $this->name;
    }
}