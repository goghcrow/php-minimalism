<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/15
 * Time: 下午8:45
 */

namespace Minimalism\Scheme\Ast;


use Minimalism\Scheme\Scope;
use Minimalism\Scheme\Value\Value;

/**
 * Class Delimiter
 * @package Minimalism\Scheme\Ast
 *
 * parser 分隔符 () [] {} .
 */
class Delimiter extends Node
{
    /* @var string */
    public $shape;

    public function __construct($shape, $file, $start, $end, $line, $col)
    {
        parent::__construct($file, $start, $end, $line, $col);
        $this->shape = $shape;
    }

    /**
     * @param Scope $s
     * @return Value
     */
    public function interp(Scope $s)
    {
        return null;
    }

    /**
     * @param Scope $s
     * @return Value
     */
    public function typecheck(Scope $s)
    {
        return null;
    }

    public function __toString()
    {
        return $this->shape;
    }
}