<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/1/29
 * Time: 下午12:55
 */

namespace Minimalism\Scheme\Ast;


use Minimalism\Scheme\Scope;
use Minimalism\Scheme\Value\Value;

class Quote extends Node
{
    /* @var Node[] List<Node> */
    public $statements;

    public function __construct(array $statements, $file, $start, $end, $line, $col)
    {
        parent::__construct($file, $start, $end, $line, $col);
        $this->statements = $statements;
    }

    /**
     * @param Scope $s
     * @return Value
     */
    public function interp(Scope $s)
    {
        // todo scope
//        return Quote
    }

    /**
     * @param Scope $s
     * @return Value
     */
    public function typecheck(Scope $s)
    {
        // TODO: Implement typecheck() method.
    }

    public function __toString()
    {
        $sep = count($this->statements) > 5 ? "\n" : " ";
        return "(quote$sep" . implode($sep, $this->statements) . ")";
    }
}