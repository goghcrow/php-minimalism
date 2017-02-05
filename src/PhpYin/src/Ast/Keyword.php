<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/15
 * Time: 下午4:24
 */

namespace Minimalism\Scheme\Ast;


use Minimalism\Scheme\Interpreter;
use Minimalism\Scheme\Scope;

/**
 * Class Keyword
 * @package Minimalism\Scheme\Ast
 *
 * :\\w.* 匹配正则
 */
class Keyword extends Node
{
    /* @var string */
    public $id;

    public function __construct($id, $file, $start, $end, $line, $col)
    {
        parent::__construct($file, $start, $end, $line, $col);
        $this->id = $id;
    }

    public function asName() {
        return new Name($this->id, $this->file, $this->start, $this->end, $this->line, $this->col);
    }

    public function interp(Scope $s)
    {
        Interpreter::abort("keyword used as value", $this);
    }

    public function typecheck(Scope $s)
    {
        Interpreter::abort("keyword used as value", $this);
    }

    public function __toString()
    {
        return ":$this->id";
    }

    public function __toAst()
    {
        return ":$this->id";
    }
}