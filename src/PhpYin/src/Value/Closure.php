<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/14
 * Time: 下午11:59
 */

namespace Minimalism\Scheme\Value;


use Minimalism\Scheme\Ast\Fun;
use Minimalism\Scheme\Constants;
use Minimalism\Scheme\Scope;

/**
 * Class Closure
 * @package Minimalism\Scheme\Value
 */
class Closure extends Value
{
    /* @var Fun 函数定义 */
    public $fun;
    /* @var Scope */
    public $properties;
    /* @var Scope 环境, 作用域 */
    public $env;

    public function __construct(Fun $fun, Scope $properties, Scope $env)
    {
        $this->fun = $fun;
        $this->properties = $properties;
        $this->env = $env;
    }

    public function __toString()
    {
//        $keyword = Constants::FUN_KEYWORD;
//        $params = implode(" ", $this->fun->params);
//        return "($keyword ($params) $this->body)";
//
//        foreach ($this->properties as $item) {
//
//        }
        return $this->fun->__toString();
    }
}