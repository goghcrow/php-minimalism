<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/2
 * Time: 下午7:57
 */

namespace Minimalism\Async\Interpret;


// 2. TODO 程序主体 与 body 添加seq关键词
// 3. TODO 理解call/ccc
// 4. TODO 做代码生成


// @see https://typeof.net/2014/m/trailer-from-a-interpreter-to-abstract-interpretation.html
// continuation context ctx k
// 如果将一个解释器转换为CPS形式,
// 那么就可以很容易的实现像 scheme中call/cc, let/cc这样可以获得当前continuation的结构,
// 因为解释器的continuation代表的解 释器接下来要进行的计算,
// 而解释器是用来模拟用户程序的, 所以实际上这个 continuation也可以看作是用户程序接下来要完成的计算,
// 也就是用户程序当前的 continuation.
// 有趣的是，这个解释器的框架代码可以沿用到编译器里：
// 从本质上来说，「编译」也是「解释」的一种。

/**
 * Class Interpreter
 * @package Minimalism\Async\Interpret
 *
 * 词法作用域
 * Lambda 抽象
 * call/cc
 * curry
 */
final class Interpreter
{
    private function buildInitScope()
    {
        $env = new Scope();

        foreach (get_defined_functions()["internal"] as $f) {
            $arity = (new \ReflectionFunction($f))->getNumberOfRequiredParameters();
            $env[$f] = self::defun($f, $arity);
        }

        $env["true"] = true;
        $env["false"] = false;
        $env["null"] = null;
        // $env["seq"] = self::defun(function(...$args) { return end($args); });
        $env["+"] = self::defun(function(...$args) { return $args[0] + $args[1]; }, 2);
        $env["-"] = self::defun(function(...$args) { return $args[0] - $args[1]; }, 2);
        $env["*"] = self::defun(function(...$args) { return $args[0] * $args[1]; }, 2);
        $env["/"] = self::defun(function(...$args) { return $args[0] / $args[1]; }, 2);
        $env["and"] = self::defun(function(...$args) { return $args[0] and $args[1]; }, 2);
        $env["or"] = self::defun(function(...$args) { return $args[0] or $args[1]; }, 2);
        $env["not"] = self::defun(function(...$args) { return !$args[0]; }, 1);
        $env["eq"] = self::defun(function(...$args) { return $args[0] === $args[1]; }, 2);
        $env["="] = self::defun(function(...$args) { return $args[0] == $args[1]; }, 2);
        $env["<"] = self::defun(function(...$args) { return $args[0] < $args[1]; }, 2);
        $env["<="] = self::defun(function(...$args) { return $args[0] <= $args[1]; }, 2);
        $env[">"] = self::defun(function(...$args) { return $args[0] > $args[1]; }, 2);
        $env[">="] = self::defun(function(...$args) { return $args[0] >= $args[1]; }, 2);
        $env["echo"] = self::defun(function(...$args) { echo $args[0]; }, 1);

        $env["string-append"] = self::defun(function(...$args) { return $args[0] . $args[1]; }, 2);
        $env["empty"] = self::defun(function(...$args) { return empty($args[0]); }, 1);

        /*
        $env["echo"] = function($k) {
            return function($v1) use($k) {
                // continuation需要一个返回值
                // echo 非函数，这里返回null
                echo $v1;
                return $k(null);
            };
        };
        */

        return $env;
    }

    public function interp($ast)
    {
        set_error_handler(function($errno, $errstr, $errfile, $errline/*, $errcontext*/) {
            echo "[$errfile:$errline]::$errstr\n\n";
            debug_print_backtrace();
            exit($errno);
        });

        $id = function() {
            assert(func_num_args() === 1);
            return func_get_arg(0);
        };

        $env = $this->buildInitScope();
        return $this->interp1($ast, new Scope($env), $id);
    }

    /**
     * cps interp
     * @param $ast
     * @param Scope $env
     * @param callable $k k is the continuation
     * @return mixed
     */
    public function interp1($ast, Scope $env, $k)
    {
        if (is_array($ast)) {
            if (empty($ast)) {
                return $k(null);
            }

            switch ($ast[0]) {
                // seq 非必须，可以作为函数存在
                case Constants::SEQ_KEYWORD:
                    assert(count($ast) > 1);
                    $statements = array_slice($ast, 1);
                    $env1 = new Scope($env);
                    return $this->interpArgs($statements, $env1, function($results) use($k) {
                        return $k(end($results));
                    });

                // define 也非必须，语法糖
                case Constants::DEF_KEYWORD:
                    assert(count($ast) === 3);
                    list(, $name, $value) = $ast;
                    assert(is_string($name));
                    return $this->interp1($value, $env, function($v) use($k, $name, $env) {
                        $env[$name] = $v;
                        return $k(null);
                    });

                case Constants::FUN_KEYWORD:
                    assert(count($ast) === 3);
                    list(, $params, $body) = $ast;
                    if ($body === null) {
                        $body = [];
                    }
                    return $this->interpDefun($params, $body, $env, $k);

                case Constants::IF_KEYWORD:
                    assert(count($ast) === 4);
                    list(, $test, $then, $else) = $ast;
                    return $this->interp1($test, $env, function($v) use($then, $else, $k, $env) {
                        if ($v) {
                            return $this->interp1($then, $env, $k);
                        } else {
                            return $this->interp1($else, $env, $k);
                        }
                    });

                case Constants::CALLCC_KEYWORD:
                    assert(count($ast) === 2);
                    $lambda = $ast[1];
                    return $this->interp1($lambda, $env, function($fun) use($k) {
                        // 注释
                        $funk = function() use($k) {
                            return function($v) use($k) {
                                return $k($v);
                            };
                        };
                        $interpBody = $fun($funk);
                        return $interpBody($funk);
                    });

                case Constants::QUOTE_KEYWORD:
                    assert(count($ast) === 2);
                    return $k($ast[1]);

                // call
                default:
                    assert(count($ast) > 0);
                    $fun = $ast[0];
                    $args = array_slice($ast, 1);
                    return $this->interpCall($fun, $args, $env, $k);

            }
        } else if (is_string($ast)) {
            $name = $ast;
            return $k($env[$name]);
        } else {
            return $k($ast);
        }
    }

    private function interpDefun($params, $body, $env, $k)
    {
        // fun 在 call时候才会接收到continuation $k, 对应 $callee = $callee($k);
        return $k(function($k) use($params, $body, $env) {
            return function(...$args) use($params, $body, $env, $k) {
                // call
                $env1 = new Scope($env);
                if (count($args) < count($params)) {
                    // 参数不足 curry
                    $pos = 0;
                    foreach ($args as $pos => $arg) {
                        $env1[$params[$pos]] = $arg;
                    }
                    $params = array_values(array_slice($params, $pos + 1));
                    return $this->interpDefun($params, $body, $env1, $k);
                } else {
                    foreach ($params as $pos => $param) {
                        $env1[$param] = $args[$pos];
                    }
                    return $this->interp1($body, $env1, $k);
                }
            };
        });
    }

    private function interpCall($closure, $args, $env, $k)
    {
        return $this->interp1($closure, $env, function($callee) use($env, $k, $args) {
            assert(is_callable($callee));
            return $this->interpArgs($args, $env, function($args) use($k, $callee) {
                // 接受延续，使用闭包保存
                $callee = $callee($k);
                if ($args === null) {
                    return $callee();
                } else {
                    return $callee(...$args);
                }
            });
        });
    }

    private function interpArgs(array $args, $env, $k)
    {
        if (empty($args)) {
            return $k(null);
        } else {
            $arg0 = $args[0];
            $args = array_slice($args, 1);
            return $this->interp1($arg0, $env, function($arg0) use($args, $env, $k) {
                if (empty($args)) {
                    return $k([$arg0]);
                } else {
                    return $this->interpArgs($args, $env, function($args) use($arg0, $k) {
                        array_unshift($args, $arg0);
                        return $k($args);
                    });
                }
            });
        }
    }

    /**
     * php fun to cps fun
     * @param callable $f
     * @param int $arity -1: va
     * @return \Closure
     */
    public static function defun(callable $f, $arity = -1)
    {
        return function($k) use($f, $arity) {
            return function(...$args) use($f, $k,$arity) {
                if ($arity !== -1) {
                    // 兼容php默认参数，允许多传递参数
                    assert(count($args) >= $arity);
                }
                return $k($f(...$args));
            };
        };
    }
}