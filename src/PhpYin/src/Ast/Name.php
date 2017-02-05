<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/13
 * Time: 上午1:26
 */

namespace Minimalism\Scheme\Ast;


use Minimalism\Scheme\Interpreter;
use Minimalism\Scheme\Scope;
use Minimalism\Scheme\Value\Value;


/**
 * Class Name
 * @package Minimalism\Scheme\Ast
 *
 * symbol
 * name->id 作为 scope 的key
 * name的Value要lookup scope获取
 */
class Name extends Node
{
    public $id;

    public function __construct($id, $file, $start, $end, $line, $col)
    {
        $this->id = $id;
        parent::__construct($file, $start, $end, $line, $col);
    }

    /**
     * @param Scope $s
     * @return Value
     */
    public function interp(Scope $s)
    {
        return $s->lookupValue($this->id);
    }

    /**
     * @param Scope $s
     * @return Value
     */
    public function typecheck(Scope $s)
    {
        $value = $s->lookupValue($this->id);
        if ($value === null) {
            Interpreter::abort("unbound variable: $this->id", $this);
            return Value::$VOID;
        } else {
            return $value;
        }
    }

    public function __toString()
    {
        return $this->id;
    }

    public function __toAst()
    {
        return "$this->id";
    }
}