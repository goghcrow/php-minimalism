<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/15
 * Time: 下午5:52
 */

namespace Minimalism\Scheme\Ast;


use Minimalism\Scheme\Binder;
use Minimalism\Scheme\Constants;
use Minimalism\Scheme\Interpreter;
use Minimalism\Scheme\Scope;
use Minimalism\Scheme\Value\Value;

class Define extends Node
{
    /* @var Node */
    public $pattern;
    /* @var Node */
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
        // TODO
        if ($this->value instanceof Name && $valueValue === null) {
            Interpreter::abort("undefined name: {$this->value->id}", $this->value);
        }
        // 在当前环境中查重 并加入当前环境
        Binder::checkDup($this->pattern);
        Binder::define($this->pattern, $valueValue, $s);
        // define 无返回值
        return Value::$VOID;
    }

    /**
     * @param Scope $s
     * @return Value
     */
    public function typecheck(Scope $s)
    {
        $t = $this->value->typecheck($s);
        Binder::checkDup($this->pattern);
        Binder::define($this->pattern, $t, $s);
        return Value::$VOID;

    }

    public function __toString()
    {
        return "(" . Constants::DEF_KEYWORD . " $this->pattern $this->value)";
    }
}