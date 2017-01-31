<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/1/29
 * Time: 下午10:09
 */

namespace Minimalism\Scheme\Value\Primitives;


use Minimalism\Scheme\Ast\Node;
use Minimalism\Scheme\Value\PrimFun;
use Minimalism\Scheme\Value\Value;

class Partial extends PrimFun
{
    /* @var $primfun PrimFun */
    public $primfun;

    /* @var Value[] */
    public $args;

    /**
     * Partial constructor.
     * @param PrimFun $primfun
     * @param Value[] $args
     */
    public function __construct(PrimFun $primfun, array $args)
    {
        $c = count($args);
        // TODO rename
        $name = "$primfun->name:$c";
        $left = $primfun->arity - $c;
        parent::__construct($name, $left);
        $this->primfun = $primfun;
        $this->args = $args;
    }

    /**
     * @param Value[] $args Values
     * @param Node $location
     * @return Value
     */
    public function apply(array $args, Node $location)
    {
        array_push($this->args, ...$args);
        return $this->primfun->apply($this->args, $location);
    }

    /**
     * @param Value[] $args Types
     * @param Node $location
     * @return Value
     */
    public function typecheck(array $args, Node $location)
    {
        // TODO: Implement typecheck() method.
    }
}