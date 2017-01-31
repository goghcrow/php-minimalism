<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/15
 * Time: 下午8:46
 */

namespace Minimalism\Scheme\Ast;


use Minimalism\Scheme\Scope;
use Minimalism\Scheme\Value\Value;

class BigInt extends Node
{
    // TODO BC

    /**
     * @param Scope $s
     * @return Value
     */
    public function interp(Scope $s)
    {
        // TODO: Implement interp() method.
    }

    /**
     * @param Scope $s
     * @return Value
     */
    public function typecheck(Scope $s)
    {
        // TODO: Implement typecheck() method.
    }

    public function __toString()
    {
        // TODO: Implement __toString() method.
    }
}