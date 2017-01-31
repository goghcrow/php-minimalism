<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/13
 * Time: 上午1:12
 */

namespace Minimalism\Scheme\Ast;


use Minimalism\Scheme\Binder;
use Minimalism\Scheme\Constants;
use Minimalism\Scheme\Scope;
use Minimalism\Scheme\Value\Value;

class Assign extends Node
{
    public $pattern;
    public $value;


    public function __construct(Node $pattern, Node $value, $file, $start, $end, $line, $col)
    {
        parent::__construct($file, $start, $end, $line, $col);
        $this->pattern = $pattern;
        $this->value = $value;
    }

    /**
     * @param Scope $s
     * @return Value
     */
    public function interp(Scope $s)
    {
        $valueValue = $this->value->interp($s);
        Binder::checkDup($this->pattern);
        Binder::assign($this->pattern, $valueValue, $s);
        return Value::$VOID;
    }

    /**
     * 赋值无返回值
     * @param Scope $s
     * @return Value
     */
    public function typecheck(Scope $s)
    {
        $valueValue = $this->value->typecheck($s);
        Binder::checkDup($this->pattern);
        Binder::assign($this->pattern, $valueValue, $s);
        return Value::$VOID;
    }

    public function __toString()
    {
        $keyword = Constants::ASSIGN_KEYWORD;
        return "($keyword $this->pattern $this->value)";
    }
}