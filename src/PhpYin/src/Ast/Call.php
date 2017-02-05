<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/15
 * Time: 下午5:14
 */

namespace Minimalism\Scheme\Ast;


use Minimalism\Scheme\Constants;
use Minimalism\Scheme\Interpreter;
use Minimalism\Scheme\Scope;
use Minimalism\Scheme\TypeChecker;
use Minimalism\Scheme\Value\BoolValue;
use Minimalism\Scheme\Value\Closure;
use Minimalism\Scheme\Value\BuildInFun;
use Minimalism\Scheme\Value\FloatValue;
use Minimalism\Scheme\Value\FunType;
use Minimalism\Scheme\Value\IntValue;
use Minimalism\Scheme\Value\PrimFun;
use Minimalism\Scheme\Value\Primitives\Partial;
use Minimalism\Scheme\Value\RecordType;
use Minimalism\Scheme\Value\RecordValue;
use Minimalism\Scheme\Value\StringValue;
use Minimalism\Scheme\Value\Type;
use Minimalism\Scheme\Value\Value;

/**
 * Class Call
 * @package Minimalism\Scheme\Ast
 *
 * 函数调用
 */
class Call extends Node
{
    /* @var Node */
    public $op;
    /* @var Argument 实参 */
    public $args;

    public function __construct(Node $op, Argument $args, $file, $start, $end, $line, $col)
    {
        parent::__construct($file, $start, $end, $line, $col);
        $this->op = $op;
        $this->args = $args;
    }

    /**
     * @param Scope $s
     * @return Value
     *
     * (op args)
     * op :
     * 原始函数
     * 闭包
     * RecordType构造函数
     */
    public function interp(Scope $s)
    {
        // 1.
        $opv = $this->op->interp($s);

        if ($opv instanceof Closure) {

            $closure = $opv;
            $funScope = new Scope($closure->env);
            $params = $closure->fun->params;

            // 2.
            // set default values for parameters
            Declare_::mergeDefault($closure->properties, $funScope);

            // 3. 绑定实参到闭包作用域
            if (!empty($this->args->positional)) {
                if (empty($this->args->keywords)) {
                    // list 形式参数
                    foreach ($this->args->positional as $pos => $arg) {
                        $argValue = $arg->interp($s);
                        if ($argValue === null) {
                            Interpreter::abort("undefined name: $arg", $arg);
                        }
                        // 参数加入闭包环境
                        $funScope->putValue($params[$pos]->id, $argValue);
                    }
                } else {
                    // 支持 keyword arguments
                    // try to bind all arguments
                    // 遍历形参，按照Name从实参获取传递值加入闭包环境
                    foreach ($params as $param/* @var $param Name */) {
                        if (isset($this->args->keywords[$param->id])) {
                            $actual = $this->args->keywords[$param->id];
                            $value = $actual->interp($funScope);
                            $funScope->putValue($param->id, $value);
                        } else {
                            // TODO
                            // 实参为传递改参数 $param->id
                        }
                    }
                }
            }


            if (count($params)) {
                if (empty($this->args->keywords)) {
                    if (count($this->args->positional) < count($params)) {
                        $leftParams = array_slice($params, count($this->args->positional));
                        $newFun = new Fun($leftParams,
                            // propForm 已经合并，不需要再次传递 $closure->properties
                            new Scope,
                            $closure->fun->body,
                            $this->op->file,
                            $this->op->start,
                            $this->op->end,
                            $this->op->line,
                            $this->op->col);
                        // prop 已经求值过，此处不需要再次传递
                        $newProp = new Scope;
                        return new Closure($newFun, $newProp, $funScope);
                    }
                } else {
                    $leftParams = $params;
                    foreach ($params as $k => $param/* @var $param Name */) {
                        $find = $funScope->lookupValue($param->id);
                        if ($find !== null) {
                            unset($leftParams[$k]);
                        }
                    }
                    if (!empty($leftParams)) {
                        $newFun = new Fun($leftParams,
                            // propForm 已经合并，不需要再次传递 $closure->properties
                            new Scope,
                            $closure->fun->body,
                            $this->op->file,
                            $this->op->start,
                            $this->op->end,
                            $this->op->line,
                            $this->op->col);
                        // prop 已经求值过，此处不需要再次传递
                        $newProp = new Scope;
                        return new Closure($newFun, $newProp, $funScope);
                    }
                }
            }

            // 4. 使用闭包作用域解释函数体
            return $closure->fun->body->interp($funScope);

        } else if ($opv instanceof RecordType) {
            // Record 类型构造函数

            $template = $opv;
            $props = new Scope();

            // set default values for fields
            Declare_::mergeDefault($template->properties, $props);

            // 只支持keyword形式构造函数
            if (!empty($this->args->keywords)) {
                foreach ($template->properties->table as $k => $_) {
                    if (isset($this->args->keywords[$k])) {
                        $arg = $this->args->keywords[$k];
                        $argValue = $arg->interp($s);
                        if ($argValue === null) {
                            Interpreter::abort("undefined name: $arg", $arg);
                        }
                        $props->putValue($k, $argValue);
                    }
                }
            }

            // 用函数调用的形式实例化一个record
            // instantiate
            return new RecordValue($template->name, $template, $props);
        } else if ($opv instanceof PrimFun) {
            $prim = $opv;
            $args = Node::interpList($this->args->positional, $s);
            if ($prim->arity >= 0 && count($args) < $prim->arity) {
                return new Partial($prim, $args);
            } else if (count($args) > $prim->arity) {
                Interpreter::abort("calling function with wrong number of arguments. expected: $prim->arity actual: "
                    . count($args), $this->args->positional[0]);
            } else {
                return $prim->apply($args, $this);
            }
        } else if ($opv instanceof BuildInFun) {
            $prim = $opv;
            $args = Node::interpList($this->args->positional, $s);
            return $prim->apply($args, $this, $s);
        } else {
            // can't happen
            $op = $opv ?: $this->op; // opv 可能为null
            Interpreter::abort("calling non-function: $op", $this->op);
            return Value::$VOID;
        }
    }

    /**
     * @param Scope $s
     * @return Value
     */
    public function typecheck(Scope $s)
    {
        /* @var Value $fun */
        $fun = $this->op->typecheck($s);
        if ($fun instanceof FunType) {
            $funtype = $fun;
//            TypeChecker.self.uncalled.remove(funtype);

            $funScope = new Scope($funtype->env);
            /* @var Name[] $params List<Name> */
            $params = $funtype->fun->params;

            // set default values for parameters
            if ($funtype->properties !== null) {
                Declare_::mergeType($funtype->properties, $funScope);
            }

            if (!empty($this->args->positional) && empty($this->args->keywords)) {
                // positional
                if (count($this->args->positional) !== count($params)) {
                    Interpreter::abort("calling function with wrong number of arguments. expected: " . count($params)
                        . " actual: " . count($this->args->positional), $this->op);
                }

                foreach ($this->args->positional as $i => $v) {
                    $value = $v->typecheck($s);
                    $expected = $funScope->lookupValue($params[$i]->id);
                    if (!Type::subtype($value, $expected, false)) {
                        Interpreter::abort("type error. expected: $expected, actual: $value", $v);
                    }
                    $funScope->putValue($params[$i]->id, $value);
                }
            } else {
                // keywords
                /* @var array $seen Set<String>*/
                $seen = [];

                // try to bind all arguments
                foreach ($params as /* @var Name $param */$param) {
                    if (isset($this->args->keywords[$param->id])) {
                        /* @var Node $actual */
                        $actual = $this->args->keywords[$param->id];
                        $seen[$param->id] = true;
                        $value = $actual->typecheck($funScope);
                        $expected = $funScope->lookupValue($param->id);
                        if (!Type::subtype($value, $expected, false)) {
                            Interpreter::abort("type error. expected: $expected, actual: $value", $actual);
                        }
                        $funScope->putValue($param->id, $value);
                    } else {
                        Interpreter::abort("argument not supplied for: $param", $this);
                        return Value::$VOID;
                    }
                }

                // detect extra arguments
                /* @var array $extra extra */
                $extra = [];
                foreach ($this->args as $id => $v) {
                    if (!isset($seen[$id])) {
                        $extra[$id] = true;
                    }
                }

                if (!empty($extra)) {
                    Interpreter::abort("extra keyword arguments: $extra", $this);
                    return Value::$VOID;
                }
            }

            $retType = $funtype->properties->lookupLocalProperty(Constants::RETURN_ARROW, "type");
            if ($retType != null) {
                if ($retType instanceof Node) {
                    // evaluate the return type because it might be (typeof x)
                    return $retType->typecheck($funScope);
                } else {
                    Interpreter::abort("illegal return type: $retType");
                    return null;
                }
            } else {
                if (TypeChecker::$self->callStack->contains($fun)) {
                    Interpreter::abort("You must specify return type for recursive functions: $this->op", $this->op);
                    return null;
                }

                TypeChecker::$self->callStack->attach($fun);
                /* @var Value $actual */
                $actual = $funtype->fun->body->typecheck($funScope);
                TypeChecker::$self->callStack->detach($fun);
                return $actual;
            }
        } else if ($fun instanceof RecordType) {
            $template = $fun;
            $values = new Scope();

            // set default values for fields
            Declare_::mergeDefault($template->properties, $values);

            // set actual values, overwrite defaults if any
            foreach ($this->args->keywords as $k => $v) {
                if (!isset($template->properties[$k])) {
                    Interpreter::abort("extra keyword argument: $k", $this);
                }

                $actual = $this->args->keywords[$k]->typecheck($s);
                $expected = $template->properties->lookupLocalType($k);
                if (!Type::subtype($actual, $expected, false)) {
                    Interpreter::abort("type error. expected: $expected, actual: actual", $this);
                }
                $values->putValue($k, $v->typecheck($s));
            }

            // check uninitialized fields
            foreach ($template->properties as $field => $property) {
                if ($values->lookupLocalValue($field) === null) {
                    Interpreter::abort("field is not initialized: $field", $this);
                }
            }
            // instantiate
            return new RecordValue($template->name, $template, $values);
        } else if ($fun instanceof PrimFun) {
            $prim = $fun;
            // TODO: 检查参数个数， 这里考虑下是否可以容忍多余参数
            // arity = -1 不定参数
            // TODO 因为支持curry，这里应该不校对参数数目 ！！！
            if ($prim->arity >= 0 && count($this->args->positional) !== $prim->arity) {
                Interpreter::abort("incorrect number of arguments for primitive $prim->name, expecting $prim->arity, but got " . count($this->args->positional), $this);
                return null;
            } else {
                /* @var Value[] List<Value> */
                $args = Node::typecheckList($this->args->positional, $s);
                return $prim->typecheck($args, $this);
            }
        } else {
            Interpreter::abort("calling non-function: $fun", $this->op);
            return Value::$VOID;
        }
    }

    public function __toString()
    {
        if (count($this->args->positional)) {
            return "($this->op $this->args)";
        } else {
            return "($this->op)";
        }
    }

    public function __toAst()
    {
        $call = [ $this->op->__toAst() ];
        foreach ($this->args->positional as $pos => $arg) {
            $call[] = $arg->__toAst();
        }
        return $call;
    }
}