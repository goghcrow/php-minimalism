<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/15
 * Time: 下午7:03
 */

namespace Minimalism\Scheme\Ast;


use Minimalism\Scheme\Scope;
use Minimalism\Scheme\Value\IntValue;
use Minimalism\Scheme\Value\Type;
use Minimalism\Scheme\Value\Value;

class IntNum extends Node
{
    /* @var string */
    public $content;
    /* @var int */
    public $value;
    /* @var int */
    public $base;

    public function __construct($content, $file, $start, $end, $line, $col)
    {
        parent::__construct($file, $start, $end, $line, $col);
        $this->content = $content;

        $sign = 1;
        if (substr($content, 0, 1) === "+") {
            $sign = 1;
            $content = substr($content, 1);
        } else if (substr($content, 0, 1) === "-") {
            $sign = -1;
            $content = substr($content, 1);
        } else {
            $sign = 1;
        }

        if (substr($content, 0, 2) === "0b") {
            $base = 2;
            $content = substr($content, 2);
        } else if (substr($content, 0, 2) === "0x") {
            $base = 16;
            $content = substr($content, 2);
        } else if (substr($content, 0, 2) === "0o") {
            $base = 8;
            $content = substr($content, 2);
        } else {
            $base = 10;
        }

        // TODO filter_var
        $this->value = intval($content, $base);
        if ($sign === -1) {
            $this->value = -$this->value;
        }
    }

    /**
     * @param string $content
     * @param string $file
     * @param int $start
     * @param int $end
     * @param int $line
     * @param int $col
     * @return IntNum|null
     */
    public static function parse($content, $file, $start, $end, $line, $col)
    {
        $int = filter_var($content, FILTER_VALIDATE_INT, FILTER_FLAG_ALLOW_OCTAL | FILTER_FLAG_ALLOW_HEX);
        if ($int === false) {
            return null;
        } else {
            return new IntNum($int, $file, $start, $end, $line, $col);
        }
    }

    /**
     * @param Scope $s
     * @return Value
     */
    public function interp(Scope $s)
    {
        return new IntValue($this->value);
    }

    /**
     * @param Scope $s
     * @return Value
     */
    public function typecheck(Scope $s)
    {
        return Type::$INT;
    }

    public function __toString()
    {
        return strval($this->value);
    }

    public function __toAst()
    {
        return ["quote", $this->value];
    }
}