<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/15
 * Time: 下午7:43
 */

namespace Minimalism\Scheme\Ast;


use Minimalism\Scheme\Constants;
use Minimalism\Scheme\Interpreter;
use Minimalism\Scheme\Scope;
use Minimalism\Scheme\Value\RecordType;
use Minimalism\Scheme\Value\Value;

class RecordDefine extends Node
{
    /* @var Name */
    public $name;
    /* @var Name[] */
    public $parents;
    /* @var Scope */
    public $propertyForm;
    /* @var Scope */
    public $properties;

    /**
     * RecordDef constructor.
     * @param Name $name
     * @param Name[] $parents List<Name>
     * @param Scope $propertyForm
     * @param string $file
     * @param int $start
     * @param int $end
     * @param int $line
     * @param int $col
     */
    public function __construct(Name $name, array $parents, Scope $propertyForm,
                                $file, $start, $end, $line, $col)
    {
        parent::__construct($file, $start, $end, $line, $col);
        $this->name = $name;
        $this->parents = $parents;
        $this->propertyForm = $propertyForm;
    }

    /**
     * @param Scope $s
     * @return Value
     */
    public function interp(Scope $s)
    {
        $properties = Declare_::evalProperties($this->propertyForm, $s);

        foreach ($this->parents as $p) {
            $pv = $p->interp($s);
            // record继承属性
            if ($pv instanceof RecordType) {
                // TODO 弄明白覆盖关系
                $properties->putAll($pv->properties);
            } else {
                // TODO
                Interpreter::abort("parent value must be record", $p);
            }
        }

        $r = new RecordType($this->name->id, $this, $properties);
        $s->putValue($this->name->id, $r);
        return $r;
    }

    /**
     * @param Scope $s
     * @return Value
     */
    public function typecheck(Scope $s)
    {
        $properties = Declare_::typecheckProperties($this->propertyForm, $s);

        foreach ($this->parents as $p) {
            $pv = $p->typecheck($s);
            if (!($pv instanceof RecordType)) {
                Interpreter::abort("parent is not a record: $pv", $p);
                return null;
            }

            $parentProps = $pv->properties;

            // check for duplicated keys
            foreach ($parentProps as $key => $prop) {
                $existing = $properties->lookupLocalType($key);
                if ($existing !== null) {
                    Interpreter::abort("conflicting field $key inherited from parent $p: $pv", $p);
                    return null;
                }
            }

            // add all properties or all fields in parent
            $properties->putAll($parentProps);
        }

        $r = new RecordType($this->name->id, $this, $properties);
        $s->putValue($this->name->id, $r);
        return $r;
    }

    public function __toString()
    {
        $body = "";
        if ($this->parents) {
            $body .= " (" . implode(" ", $this->parents) . ")";
        }

        foreach ($this->propertyForm->table as $field => $props) {
            foreach ($props as $k => $v) {
                $body .= " :$k $v";
            }
        }

        return Constants::TUPLE_BEGIN . Constants::RECORD_KEYWORD . " {$this->name}$body" . Constants::TUPLE_END;
    }

    public function __toAst()
    {
        // TODO: Implement __toAst() method.
    }
}