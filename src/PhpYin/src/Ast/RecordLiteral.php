<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/15
 * Time: 下午5:58
 */

namespace Minimalism\Scheme\Ast;

use Minimalism\Scheme\Constants;
use Minimalism\Scheme\Interpreter;
use Minimalism\Scheme\Scope;
use Minimalism\Scheme\Value\RecordType;
use Minimalism\Scheme\Value\RecordValue;
use Minimalism\Scheme\Value\Value;


/**
 * Class RecordLiteral
 * @package Minimalism\Scheme\Ast
 *
 * record字面量
 * {:k1 v1 :k2 v2}
 */
class RecordLiteral extends Node
{
    /* @var Node[] Map<String, Node>*/
    public $map;

    /**
     * RecordLiteral constructor.
     * @param Node[] $contents List<Node>
     * @param string $file
     * @param int $start
     * @param int $end
     * @param int $line
     * @param int $col
     */
    public function __construct(array $contents, $file, $start, $end, $line, $col)
    {
        parent::__construct($file, $start, $end, $line, $col);

        if (count($contents) % 2 !== 0) {
            Interpreter::abort("record initializer must have even number of elements", $this);
        }

        for ($size = count($contents), $i = 0; $i < $size; $i += 2) {
            $key = $contents[$i];
            $value = $contents[$i + 1];
            if ($key instanceof Keyword) {
                if ($value instanceof Keyword) {
                    Interpreter::abort("keywords shouldn't be used as values: $value", $value);
                } else {
                    $this->map[$key->id] = $value;
                }
            } else {
                Interpreter::abort("record initializer key is not a keyword: $key", $key);
            }
        }
    }

    /**
     * @param Scope $s
     * @return Value
     */
    public function interp(Scope $s)
    {
        $properties = new Scope;
        foreach ($this->map as $k => $v) {
            $vV = $v->interp($s);
            if ($vV === null) {
                Interpreter::abort("undefined name: $v", $v);
            }
            $properties->putValue($k, $vV);
        }

        // TODO ???? 难道不是RecordValue
        // TODO 查找所有 instanceof RecordType的地方
        // 重新测试 define Record
        return new RecordType(null, $this, $properties);
    }

    /**
     * @param Scope $s
     * @return Value
     */
    public function typecheck(Scope $s)
    {
        $properties = new Scope;
        foreach ($this->map as $k => $v) {
            $properties->putValue($k, $v->typecheck($s));
        }
        return new RecordType(null, $this, $properties);
    }

    public function __toString()
    {
        $pairs = [];
        foreach ($this->map as $k => $v) {
            $pairs[] = ":$k $v";
        }
        $body = implode(" ", $pairs);
        return Constants::RECORD_BEGIN . $body . Constants::RECORD_END;
    }

    public function __toAst()
    {
        $record = [Constants::RECORD_KEYWORD];
        foreach ($this->map as $name => $node) {
            $record[] = [":$name", $node->__toAst()];
        }
        return $record;
    }
}