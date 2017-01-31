<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/14
 * Time: 下午11:53
 */

namespace Minimalism\Scheme\Ast;


use Minimalism\Scheme\Constants;
use Minimalism\Scheme\Scope;
use Minimalism\Scheme\TypeChecker;
use Minimalism\Scheme\Value\Closure;
use Minimalism\Scheme\Value\FunType;
use Minimalism\Scheme\Value\Value;


/**
 * Class Fun
 * @package Minimalism\Scheme\Ast
 *
 * 函数定义
 */
class Fun extends Node
{
    /* @var Name[] 形参列表 */
    public $params;
    /* @var Node */
    public $body;
    /* @var Scope */
    public $propertyForm;

    /**
     * Fun constructor.
     * @param Name[] $params
     * @param Scope $propertyForm
     * @param Node $body
     * @param string $file
     * @param int $start
     * @param int $end
     * @param int $line
     * @param int $col
     */
    public function __construct(array $params, Scope $propertyForm, Node $body, $file, $start, $end, $line, $col)
    {
        parent::__construct($file, $start, $end, $line, $col);
        $this->params = $params;
        $this->propertyForm = $propertyForm; // unevaluated property form
        $this->body = $body;
    }

    /**
     * @param Scope $s
     * @return Closure
     */
    public function interp(Scope $s)
    {
        // evaluate and cache the properties in the closure
        $properties = Declare_::evalProperties($this->propertyForm, $s);
        // 函数定义的解析结果返回闭包
        return new Closure($this, $properties, $s);
    }

    /**
     * @param Scope $s
     * @return Value
     */
    public function typecheck(Scope $s)
    {
        // evaluate and cache the properties in the closure
        $properties = Declare_::typecheckProperties($this->propertyForm, $s);
        $ft = new FunType($this, $properties, $s);
        TypeChecker::$self->uncalled->attach($ft);
        return $ft;

    }

    public function __toString()
    {
//        if (empty($this->propertyForm)) {
            $keyword = Constants::FUN_KEYWORD;
            $params = implode(" ", $this->params);
            return "($keyword ($params) $this->body)";
//        } else {
//            $propPair = [];
//            foreach ($this->propertyForm->table as $name => $props) {
//                $pairs = [];
//                foreach ($props as $pname => $prop) {
//                    $pairs[] = ":$pname $prop";
//                }
//                $propPair[] = "[$name " . implode(" ", $pairs) . "]";
//            }
//            $keyword = Constants::FUN_KEYWORD;
//            $params = implode(" ", $propPair);
//            return "($keyword ($params) $this->body)";
//        }
    }
}