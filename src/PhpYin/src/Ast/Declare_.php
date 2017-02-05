<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/15
 * Time: 上午12:10
 */

namespace Minimalism\Scheme\Ast;


use Minimalism\Scheme\Constants;
use Minimalism\Scheme\Interpreter;
use Minimalism\Scheme\Scope;
use Minimalism\Scheme\Value\Value;

class Declare_ extends Node
{
    /* @var Scope*/
    public $propertyForm;

    public function __construct(Scope $propertyForm, $file, $start, $end, $line, $col)
    {
        parent::__construct($file, $start, $end, $line, $col);
        $this->propertyForm = $propertyForm;
    }

    /**
     * @param Scope $s
     * @return Value
     */
    public function interp(Scope $s)
    {
//        mergeProperties(propsNode, s);
        return Value::$VOID;
    }

    /**
     * @param Scope $s
     * @return Value
     */
    public function typecheck(Scope $s)
    {
        return null;
    }

    public static function mergeDefault(Scope $properties, Scope $s)
    {
        foreach ($properties->table as $key => $_) {
            $defaultValue = $properties->lookupLocalProperty($key, "default");
            if ($defaultValue === null) {
                continue;
            } else if ($defaultValue instanceof Value) {
                $existing = $s->lookupValue($key);
                if ($existing === null) {
                    $s->putValue($key, $defaultValue);
                }
            } else {
                Interpreter::abort("default value is not a value, shouldn't happen");
            }
        }

        return $s;
    }

    public static function mergeType(Scope $properties, Scope $s)
    {
        foreach ($properties->table as $key => $_) {
            if ($key === Constants::RETURN_ARROW) {
                continue;
            }
            $type = $properties->lookupLocalProperty($key, "type");
            if ($type == null) {
                continue;
            } else if ($type instanceof Value) {
                $existing = $s->lookupValue($key);
                if ($existing === null) {
                    $s->putValue($key, $type);
                }
            } else {
                Interpreter::abort("illegal type, shouldn't happen $type");
            }
        }
    }

    /**
     * @param Scope $unevaled
     * @param Scope $s
     * @return Scope
     */
    public static function evalProperties(Scope $unevaled, Scope $s)
    {
        $evaled = new Scope;

        foreach ($unevaled->table as $field => $props) {
            foreach ($props as $key => $v) {
                if ($v instanceof Node) {
                    $vValue = $v->interp($s);
                    $evaled->putProperty($field, $key, $vValue);
                } else {
                    Interpreter::abort("property is not a node, parser bug: $v");
                }
            }
        }

        return $evaled;
    }

    /**
     * @param Scope $unevaled
     * @param Scope $s
     * @return Scope
     */
    public static function typecheckProperties(Scope $unevaled, Scope $s)
    {
        $evaled = new Scope();

        foreach ($unevaled->table as $field => $props) {
            if ($field === Constants::RETURN_ARROW) {
                $evaled->putProperties($field, $props);
            } else {
                foreach ($props as $key => $v) {
                    if ($v instanceof Node) {
                        $vValue = $v->typecheck($s);
                        $evaled->putProperty($field, $key, $vValue);
                    } else {
                        Interpreter::abort("property is not a node, parser bug: $v");
                    }
                }
            }
        }

        return $evaled;
    }

    public function __toString()
    {
        $body = "";
        foreach ($this->propertyForm as $field => $props) {
            foreach ($props as $key => $value) {
                $body .=" :$key $value";
            }
        }
        return Constants::TUPLE_BEGIN . Constants::DECLARE_KEYWORD . " $body" . Constants::TUPLE_END;
    }

    public function __toAst()
    {
        // TODO: Implement __toAst() method.
    }
}