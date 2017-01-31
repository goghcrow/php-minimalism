<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/15
 * Time: 下午3:56
 */

namespace Minimalism\Scheme\Value;

use Minimalism\Scheme\Ast\Fun;
use Minimalism\Scheme\Scope;


class FunType extends Value
{
    /* @var Fun */
    public $fun;
    /* @var Scope */
    public $properties;
    /* @var Scope */
    public $env;


    public function __construct(Fun $fun, Scope $properties, Scope $env)
    {
        $this->fun = $fun;
        $this->properties = $properties;
        $this->env = $env;
    }


    public function __toString()
    {
        return $this->properties->__toString();
    }
}