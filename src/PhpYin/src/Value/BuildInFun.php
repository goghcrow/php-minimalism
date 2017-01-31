<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/1/29
 * Time: 上午10:03
 */

namespace Minimalism\Scheme\Value;


use Minimalism\Scheme\Ast\Node;
use Minimalism\Scheme\Scope;

abstract class BuildInFun extends Value
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
     * @param Scope $s
     * @return Value 20170128 加入scope参数是为了实现require， 不知道是否有更好的方式
     *
     * 20170128 加入scope参数是为了实现require， 不知道是否有更好的方式
     */
    public abstract function apply(array $args, Node $location, Scope $s);

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