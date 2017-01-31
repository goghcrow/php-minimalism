<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/13
 * Time: 上午12:50
 */

namespace Minimalism\Scheme\Ast;


use Minimalism\Scheme\Constants;
use Minimalism\Scheme\Interpreter;
use Minimalism\Scheme\Scope;
use Minimalism\Scheme\Value\BoolValue;
use Minimalism\Scheme\Value\UnionType;
use Minimalism\Scheme\Value\Value;

class If_ extends Node
{
    public $test;
    public $then;
    public $orelse;

    public function __construct(Node $test, Node $then, Node $orelse, $file, $start, $end, $line, $col)
    {
        $this->test = $test;
        $this->then = $then;
        $this->orelse = $orelse;
        parent::__construct($file, $start, $end, $line, $col);
    }

    /**
     * @param Scope $s
     * @return Value
     */
    public function interp(Scope $s)
    {
        $v = $this->test->interp($s);
        /* @var $v BoolValue */
        if ($v->value) {
            return $this->then->interp($s);
        } else {
            return $this->orelse->interp($s);
        }
    }

    /**
     * @param Scope $s
     * @return Value
     */
    public function typecheck(Scope $s)
    {
        $tv = $this->test->typecheck($s);

        if (!($tv instanceof BoolValue)) {
            Interpreter::abort("test is not boolean: $tv", $this->test);
            return null;
        }

        $type1 = $this->then->typecheck($s);
        $type2 = $this->orelse->typecheck($s);

        return UnionType::union($type1, $type2);
    }

    public function __toString()
    {
        $keyword = Constants::IF_KEYWORD;
        return "($keyword $this->test $this->then $this->orelse)";
    }
}