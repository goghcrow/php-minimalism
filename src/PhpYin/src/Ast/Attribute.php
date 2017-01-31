<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/15
 * Time: 下午4:54
 */

namespace Minimalism\Scheme\Ast;


use Minimalism\Scheme\Interpreter;
use Minimalism\Scheme\Scope;
use Minimalism\Scheme\Value\RecordType;
use Minimalism\Scheme\Value\RecordValue;
use Minimalism\Scheme\Value\Value;

class Attribute extends Node
{
    /* @var Node */
    public $value;
    /* @var Name */
    public $attr;

    public function __construct(Node $value, Name $attr, $file, $start, $end, $line, $col)
    {
        parent::__construct($file, $start, $end, $line, $col);
        $this->value = $value;
        $this->attr = $attr;
    }

    /**
     * @param Scope $s
     * @return Value
     */
    public function interp(Scope $s)
    {
        $record = $this->value->interp($s);

        if ($record instanceof RecordValue) {
            $a = $record->properties->lookupLocalValue($this->attr->id);
            if ($a !== null) {
                return $a;
            } else {
                Interpreter::abort("attribute $this->attr not found in record: $record", $this->attr);
                return null;
            }
        } else {
            Interpreter::abort("getting attribute of non-record: $record", $this->attr);
            return null;
        }
    }

    /**
     * @param Scope $s
     * @return Value
     */
    public function typecheck(Scope $s)
    {
        $record = $this->value->typecheck($s);

        if ($record instanceof RecordValue) {
            $a = $record->properties->lookupLocalValue($this->attr->id);
            if ($a !== null) {
                return $a;
            } else {
                Interpreter::abort("attribute $this->attr not found in record: $record", $this->attr);
                return null;
            }
        } else {
            Interpreter::abort("getting attribute of non-record: $record", $this->attr);
            return null;
        }
    }

    // TODO
    public function set(Value $v, Scope $s)
    {
        $record = $this->value->interp($s);
        if ($record instanceof RecordType) {
            $a = $record->properties->lookupValue($this->attr->id);
            if ($a !== null) {
                $record->properties->putValue($this->attr->id, $v);
            } else {
                Interpreter::abort("can only assign to existing attribute in record, $this->attr not found in: $record", $this->attr);
            }
        } else {
            Interpreter::abort("setting attribute of non-record: $record", $this->attr);
        }
    }

    public function __toString()
    {
        return "$this->value.$this->attr";
    }
}