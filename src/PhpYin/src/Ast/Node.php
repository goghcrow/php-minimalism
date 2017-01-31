<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/13
 * Time: 上午12:46
 */

namespace Minimalism\Scheme\Ast;


use Minimalism\Scheme\Interpreter;
use Minimalism\Scheme\Scope;
use Minimalism\Scheme\Value\Value;

abstract class Node
{
    public $file;
    public $start;
    public $end;
    public $line;
    public $col;

    public function __construct($file, $start, $end, $line, $col)
    {
        $this->file = $file;
        $this->start = $start;
        $this->end = $end;
        $this->line = $line;
        $this->col = $col;
    }

    public function getFileLineCol()
    {
        $line = $this->line + 1;
        $col = $this->col + 1;
        return "$this->file:$line:$col";
    }

    /**
     * @param Node[] $nodes
     * @param Scope $s
     * @return Value[]
     */
    public static function interpList(array $nodes, Scope $s)
    {
        $values = [];
        foreach ($nodes as $node) {
            $value = $node->interp($s);
            if ($value === null && $node instanceof Name) {
                Interpreter::abort("undefined name: $node", $node);
            }
            $values[] = $value;
        }
        return $values;
    }

    /**
     * @param Node[] $nodes
     * @param Scope $s
     * @return Value[]
     */
    public static function typecheckList(array $nodes, Scope $s)
    {
        $values = [];
        foreach ($nodes as $node) {
            $values[] = $node->typecheck($s);
        }
        return $values;
    }

    /**
     * @param Scope $s
     * @return Value
     */
    public abstract function interp(Scope $s);

    /**
     * @param Scope $s
     * @return Value
     */
    public abstract function typecheck(Scope $s);

    public abstract function __toString();
}