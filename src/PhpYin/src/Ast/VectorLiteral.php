<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/15
 * Time: 下午6:21
 */

namespace Minimalism\Scheme\Ast;


use Minimalism\Scheme\Constants;
use Minimalism\Scheme\Scope;
use Minimalism\Scheme\Value\Value;
use Minimalism\Scheme\Value\Vector;

class VectorLiteral extends Node
{
    /* @var Node[] */
    public $elements;

    public function __construct(array $elements, $file, $start, $end, $line, $col)
    {
        parent::__construct($file, $start, $end, $line, $col);
        $this->elements = $elements;
    }

    /**
     * @param Scope $s
     * @return Value
     */
    public function interp(Scope $s)
    {
        return new Vector(Node::interpList($this->elements, $s));
    }

    /**
     * @param Scope $s
     * @return Value
     */
    public function typecheck(Scope $s)
    {
        return new Vector(Node::typecheckList($this->elements, $s));
    }

    public function __toString()
    {
        return Constants::VECTOR_BEGIN . implode(" ", $this->elements) . Constants::VECTOR_END;
    }

    public function __toAst()
    {
        $vec = ["vector"];
        foreach ($this->elements as $element) {
            $vec[] = $element->__toAst();
        }
        return $vec;
    }
}