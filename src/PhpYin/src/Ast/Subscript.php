<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/15
 * Time: 下午8:44
 */

namespace Minimalism\Scheme\Ast;


use Minimalism\Scheme\Interpreter;
use Minimalism\Scheme\Scope;
use Minimalism\Scheme\Value\IntValue;
use Minimalism\Scheme\Value\Value;
use Minimalism\Scheme\Value\Vector;

class Subscript extends Node
{
    /* @var Node */
    public $value;
    /* @var Node */
    public $index;

    public function __construct(Node $value, Node $index, $file, $start, $end, $line, $col)
    {
        parent::__construct($file, $start, $end, $line, $col);
        $this->value = $value;
        $this->index = $index;
    }

    // TODO
    /**
     * @param Scope $s
     * @return Value
     */
    public function interp(Scope $s)
    {
        $vector = $this->value->interp($s);
        $indexValue = $this->index->interp($s);

        if (!($vector instanceof Vector)) {
            Interpreter::abort("subscripting non-vector: vector", $this->value);
        }

        if (!($indexValue instanceof IntValue)) {
            Interpreter::abort("subscript $this->index is not an integer: $indexValue", $this->value);
        }


        $values = $vector->values;
        $i = $indexValue->value;

        if ($i >= 0 && $i < count($values)) {
            return $values[$i];
        } else {
            Interpreter::abort("subscript out of bound: $i v.s. [0, " . (count($values) - 1) . "]", $this);
            return null;
        }
    }

    public function set(Value $v, Scope $s)
    {
        $vector = $this->value->interp($s);
        $indexValue = $this->index->interp($s);

        if (!($vector instanceof Vector)) {
            Interpreter::abort("subscripting non-vector: vector", $this->value);
        }

        if (!($indexValue instanceof IntValue)) {
            Interpreter::abort("subscript $this->index is not an integer: $indexValue", $this->value);
        }

        $vector1 = $vector;
        $i = $indexValue->value;

        if ($i >= 0 && $i < $vector1->size()) {
            $vector1->set($i, $v);
        } else {
            Interpreter::abort("subscript out of bound: $i v.s. [0, " . ($vector1->size() - 1) . "]", $this);
        }
    }

    /**
     * @param Scope $s
     * @return Value
     */
    public function typecheck(Scope $s)
    {
        // TODO: Implement typecheck() method.
        return null;
    }

    public function __toString()
    {
        return "(ref $this->value $this->index)";
    }
}