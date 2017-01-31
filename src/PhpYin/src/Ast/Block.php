<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/15
 * Time: 下午5:07
 */

namespace Minimalism\Scheme\Ast;


use Minimalism\Scheme\Interpreter;
use Minimalism\Scheme\Scope;
use Minimalism\Scheme\Value\Value;


/**
 * Class Block
 * @package Minimalism\Scheme\Ast
 *
 * 1. seq 关键字 或者 程序本身
 * 2. 顺序执行statement，返回最后一项结果
 */
class Block extends Node
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
        // !!! Block 通过parent Scope访问upValue, 新建scope， 保存解析过程中新加入属性
        $s = new Scope($s);
        $result = null;
        foreach ($this->statements as $statement) {
            $result = $statement->interp($s);
            if ($statement instanceof Name && $result === null) {
                Interpreter::abort("undefined name: $statement", $statement);
            }
        }
        // $result:
        // Block 中statement的$result不需要关系，因为statement不关心返回值
        // 最后一次返回作为Block的返回值

        // 遍历解析，每一次解析都可能往scope添加属性
        // 下一次解析可以使用之前加入的属性
        // block 返回最后一项解析结果
        return $result;
    }

    /**
     * @param Scope $s
     * @return Value
     */
    public function typecheck(Scope $s)
    {
        $s = new Scope($s);
        $result = null;
        foreach ($this->statements as $statement) {
            $result = $statement->typecheck($s);
        }
        return $result;
    }

    public function __toString()
    {
        $sep = count($this->statements) > 5 ? "\n" : " ";
        return "(seq$sep" . implode($sep, $this->statements) . ")";
    }
}