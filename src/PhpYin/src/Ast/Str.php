<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/15
 * Time: 下午7:20
 */

namespace Minimalism\Scheme\Ast;


use Minimalism\Scheme\Scope;
use Minimalism\Scheme\Value\StringValue;
use Minimalism\Scheme\Value\Type;
use Minimalism\Scheme\Value\Value;

class Str extends Node
{
    /* @var string */
    public $value;

    public function __construct($value, $file, $start, $end, $line, $col)
    {
        parent::__construct($file, $start, $end, $line, $col);

        // TODO 16进制 2进制 字符串
        $this->value = str_replace([
            '\a', '\b', '\f', '\n', '\r', '\t', '\v', '\\\\', '\\\'', '\?', '\0',
        ], [
            "\a", "\b", "\f", "\n", "\r", "\t", "\v", "\\\\", "\'", "\?", "\0",
        ], $value);
    }

    /**
     * @param Scope $s
     * @return Value
     */
    public function interp(Scope $s)
    {
        return new StringValue($this->value);
    }

    /**
     * @param Scope $s
     * @return Value
     */
    public function typecheck(Scope $s)
    {
        return Type::$STRING;
    }

    public function __toString()
    {
        return "\"$this->value\"";
    }

    public function __toAst()
    {
        return ["quote", "$this->value"];
    }
}