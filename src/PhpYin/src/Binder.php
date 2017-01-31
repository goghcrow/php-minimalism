<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/13
 * Time: 上午1:22
 */

namespace Minimalism\Scheme;


use Minimalism\Scheme\Ast\Attribute;
use Minimalism\Scheme\Ast\Name;
use Minimalism\Scheme\Ast\Node;
use Minimalism\Scheme\Ast\RecordLiteral;
use Minimalism\Scheme\Ast\Subscript;
use Minimalism\Scheme\Ast\VectorLiteral;
use Minimalism\Scheme\Value\RecordType;
use Minimalism\Scheme\Value\Value;
use Minimalism\Scheme\Value\Vector;

/**
 * Class Binder
 * @package Minimalism\Scheme
 *
 * 变量绑定值
 */
class Binder
{
    /**
     * @param Node $pattern Name|RecordLiteral|VectorLiteral
     * @param Value $value Value|RecordType|Vector
     * @param Scope $env 绑定作用域
     *
     * Name => Value
     * RecordLiteral => RecordType
     * VectorLiteral => VectorValue
     *
     * define 是递归进行的， 决定了define的pattern与value也可以是递归的结构, 比如
     * (define [a [b c] d] [1 [2 3] 4])
     */
    public static function define(Node $pattern, Value $value, Scope $env)
    {
        if ($pattern instanceof Name) {
            $id = $pattern->id;
            // 本地作用域不允许重复定义，但可以覆盖父级作用域`变量`
            $v = $env->lookupLocalValue($id);
            if ($v !== null) {
                Interpreter::abort("trying to redefine name: $id", $pattern);
            } else {
                $env->putValue($id, $value);
            }
        } else if ($pattern instanceof RecordLiteral) {
            if ($value instanceof RecordType) {
                $elms1 = $pattern->map; // Map<String, Node>
                $props = $value->properties; /* @var $props Scope */
                $elms2 = $props->table; // Map<String, Value>

                $keys1 = array_keys($elms1);
                $keys2 = array_keys($elms2);
                // TODO php为非稳定排序?!
                sort($keys1);
                sort($keys2);

                // match
                if ($keys1 === $keys2) {
                    foreach ($elms1 as $k1 => $elm) {
                        $value = $props->lookupLocalValue($k1);
                        self::define($elm, $value, $env);
                    }
                } else {
                    Interpreter::abort("define with records of different attributes: " .
                        implode(",", array_keys($elms1)) . " v.s. " . implode(",", array_keys($elms1)), $pattern);
                }
            } else {
                Interpreter::abort("define with incompatible types: record and $value", $pattern);
            }
        } else if ($pattern instanceof VectorLiteral) {
            if ($value instanceof Vector) {
                $elms1 = $pattern->elements; // List<Node>
                $elms2 = $value->values; // List<Value>
                if (count($elms1) === count($elms2)) {
                    foreach ($elms1 as $i => $elm) {
                        self::define($elm, $elms2[$i], $env);
                    }
                } else {
                    Interpreter::abort("define with vectors of different sizes: " . count($elms1) . " v.s. " . count($elms2), $pattern);
                }
            } else {
                Interpreter::abort("define with incompatible types: vector and $value", $pattern);
            }
        }  else {
            Interpreter::abort("unsupported pattern of define: $pattern", $pattern);
        }
    }

    public static function assign(Node $pattern, Value $value, Scope $env)
    {
        if ($pattern instanceof Name) {
            $id = $pattern->id;
            $d = $env->findDefiningScope($id);
            if ($d === null) {
                Interpreter::abort("assigned name was not defined: $id", $pattern);
            } else {
                $d->putValue($id, $value);
            }
        } else if ($pattern instanceof Subscript) {
            $pattern->set($value, $env);
        }
        else if ($pattern instanceof Attribute) {
            $pattern->set($value, $env);
        } else if ($pattern instanceof RecordLiteral) {
            if ($value instanceof RecordType) {
                $elms1 = $pattern->map; // Map<String, Node>
                $elms2 = $value->properties; // Scope

                $keys1 = array_keys($elms1);
                $keys2 = array_keys($elms2);
                // TODO php为非稳定排序?!
                sort($keys1);
                sort($keys2);

                if ($keys1 === $keys2) {
                    foreach ($elms1 as $k1 => $elm) {
                        self::assign($elms1, $elms2->lookupLocalValue($k1), $env);
                    }
                } else {
                    Interpreter::abort("assign with records of different attributes: " .
                        implode(",", array_keys($elms1)) . " v.s. " . implode(",", array_keys($elms1)), $pattern);
                }
            } else {
                Interpreter::abort("assign with incompatible types: record and $value", $pattern);
            }
        } else if ($pattern instanceof VectorLiteral) {
            if ($value instanceof Vector) {
                $elms1 = $pattern->elements; // List<Node>
                $elms2 = $value->values; // List<Value>
                if (count($elms1) === count($elms2)) {
                    foreach ($elms1 as $i => $elm) {
                        self::assign($elm, $elms2[$i], $env);
                    }
                } else {
                    Interpreter::abort("assign vectors of different sizes: " . count($elms1) . " v.s. " . count($elms2), $pattern);
                }
            } else {
                Interpreter::abort("assign incompatible types: vector and $value", $pattern);
            }
        } else {
            Interpreter::abort("unsupported pattern of define: $pattern", $pattern);
        }
    }

    public static function checkDup(Node $pattern)
    {
        self::checkDup1($pattern);
    }

    private static function checkDup1(Node $pattern, array &$seen = [])
    {
        if ($pattern instanceof Name) {
            $id = $pattern->id;
            if (isset($seen[$id])) {
                Interpreter::abort("duplicated name found in pattern: pattern", $pattern);
            } else {
                $seen[$id] = true;
            }

        } else if ($pattern instanceof RecordLiteral) {
            foreach ($pattern->map as $node) {
                self::checkDup1($node, $seen);
            }
        } else if ($pattern instanceof VectorLiteral) {
            foreach ($pattern->elements as $node) {
                self::checkDup1($node, $seen);
            }
        }
    }
}