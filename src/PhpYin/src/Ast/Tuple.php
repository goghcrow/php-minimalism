<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/15
 * Time: 下午7:23
 */

namespace Minimalism\Scheme\Ast;


use Minimalism\Scheme\Scope;
use Minimalism\Scheme\Value\Value;


/**
 * Class Tuple
 * @package Minimalism\Scheme\Ast
 *
 * 词法分析阶段数据结构
 */
class Tuple extends Node
{
    /* @var Node[] */
    public $elements;
    /* @var Node */
    public $open;
    /* @var Node */
    public $close;

    public function __construct(array $elements, Node $open, Node $close, $file, $start, $end, $line, $col)
    {
        parent::__construct($file, $start, $end, $line, $col);
        $this->elements = $elements;
        $this->open = $open;
        $this->close = $close;
    }

    public function getHead()
    {
        if (empty($this->elements)) {
            return null;
        } else {
            return $this->elements[0];
        }
    }

    /**
     * @param Scope $s
     * @return Value
     */
    public function interp(Scope $s)
    {
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
        return ($this->open == null ? "" : $this->open) . implode(" ", $this->elements) . ($this->close === null ? "" : $this->close);
    }

    public function __toAst()
    {
        $tuple = [];
        foreach ($this->elements as $element) {
            $tuple[] = $element->__toAst();
        }
        return $tuple;
    }
}