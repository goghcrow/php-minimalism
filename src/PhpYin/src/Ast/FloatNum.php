<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/15
 * Time: 下午7:04
 */

namespace Minimalism\Scheme\Ast;


use Minimalism\Scheme\Scope;
use Minimalism\Scheme\Value\FloatValue;
use Minimalism\Scheme\Value\Type;
use Minimalism\Scheme\Value\Value;

class FloatNum extends Node
{
    /* @var string */
    public $content;
    /* @var float */
    public $value;

    public function __construct($content, $file, $start, $end, $line, $col)
    {
        parent::__construct($file, $start, $end, $line, $col);
        $this->content = $content;
        // TODO filter_var($content, FILTER_VALIDATE_FLOAT)
        $this->value = floatval($this->content);
    }

    /**
     * @param string $content
     * @param string $file
     * @param int $start
     * @param int $end
     * @param int $line
     * @param int $col
     * @return FloatNum|null
     */
    public static function parse($content, $file, $start, $end, $line, $col)
    {
        $float = filter_var($content, FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_THOUSAND);
        if ($float === false) {
            return null;
        } else {
            return new FloatNum($float, $file, $start, $end, $line, $col);
        }
    }

    /**
     * @param Scope $s
     * @return Value
     */
    public function interp(Scope $s)
    {
        return new FloatValue($this->value);
    }

    /**
     * @param Scope $s
     * @return Value
     */
    public function typecheck(Scope $s)
    {
        // TODO ???
        // return null;
        return Type::$FLOAT;
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