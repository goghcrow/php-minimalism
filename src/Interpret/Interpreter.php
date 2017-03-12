<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/2
 * Time: 下午7:57
 */

namespace Minimalism\A\Interpret;

require_once __DIR__ . "/functions.php";

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
 * @package Minimalism\A\Interpret
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
            debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            exit($errno);
        });

        $id = function() {
            abort(func_num_args() === 1, func_get_args());
            return func_get_arg(0);
        };

        $env = $this->buildInitScope();
        return $this->interp1($ast, new Scope($env), $id);
    }

    /**
     * cps interp
     * @param $ast
     * @param Scope $env 数据上下文
     * @param callable $ctx 控制流上下文 ctx is the continuation
     * @return mixed
     */
    public function interp1($ast, Scope $env, $ctx)
    {
        if (is_array($ast)) {
            if (empty($ast)) {
                return $ctx(null);
            }

            switch ($ast[0]) {
                // seq 非必须，可以作为函数存在
                case Keywords::SEQ_KEYWORD:
                    abort(count($ast) > 1, $ast);
                    // 多条statements, 独立作用域
                    $statements = array_slice($ast, 1);
                    $env1 = new Scope($env);
                    return $this->interpArgs($statements, $env1, function($results) use($ctx) {
                        // 返回最后一条statement值
                        if (empty($results)) {
                            return $ctx(null);
                        } else {
                            return $ctx(end($results));
                        }
                    });

                // define 也非必须，语法糖, 可通λ参数进行bind
                case Keywords::DEF_KEYWORD:
                    abort(count($ast) === 3, $ast);
                    list(, $name, $value) = $ast;
                    abort(is_string($name), $name);
                    return $this->interp1($value, $env, function($v) use($ctx, $name, $env) {
                        $env[$name] = $v;
                        // define 无返回值
                        return $ctx(null);
                    });

                case Keywords::FUN_KEYWORD:
                    abort(count($ast) === 3, $ast);
                    list(, $params, $body) = $ast;
                    if ($body === null) {
                        $body = [];
                    }
                    return $this->interpDefun($params, $body, $env, $ctx);

                case Keywords::IF_KEYWORD:
                    abort(count($ast) === 4, $ast);
                    list(, $test, $then, $else) = $ast;
                    return $this->interp1($test, $env, function($v) use($then, $else, $ctx, $env) {
                        if ($v) {
                            return $this->interp1($then, $env, $ctx);
                        } else {
                            return $this->interp1($else, $env, $ctx);
                        }
                    });

                case Keywords::CALLCC_KEYWORD:
                    abort(count($ast) === 2, $ast);
                    return $this->interp1($ast[1], $env, function($fun) use($ctx) {
                        $fctx = function() use($ctx) {
                            return function($v) use($ctx) {
                                return $ctx($v);
                            };
                        };
                        // 对应函数定义, 先传递ctx
                        $interpBody = $fun($fctx);
                        // 执行函数, call/cc的参数是continuation
                        return $interpBody($fctx);
                    });

                case Keywords::QUOTE_KEYWORD:
                    abort(count($ast) === 2, $ast);
                    return $ctx($ast[1]);

                // call
                default:
                    abort(count($ast) > 0, $ast);
                    $fun = $ast[0];
                    $args = array_slice($ast, 1);
                    return $this->interpCall($fun, $args, $env, $ctx);

            }
        } else if (is_string($ast)) {
            $name = $ast;
            // symbol lookup
            return $ctx($env[$name]);
        } else {
            return $ctx($ast);
        }
    }

    private function interpDefun($params, $body, $env, $ctx)
    {
        // fun 在 call时候才会接收到continuation $k, 对应 $callee = $callee($k);
        return $ctx(function($ctx) use($params, $body, $env) {
            // 函数定义实际使用了宿主语言php的函数
            return function(...$args) use($params, $body, $env, $ctx) {
                // call, 函数调用在独立的作用域
                $env1 = new Scope($env);
                if (count($args) < count($params)) {
                    // 参数不足 curry
                    $pos = 0;
                    foreach ($args as $pos => $arg) {
                        $env1[$params[$pos]] = $arg;
                    }
                    $params = array_values(array_slice($params, $pos + 1));
                    return $this->interpDefun($params, $body, $env1, $ctx);
                } else {
                    foreach ($params as $pos => $param) {
                        $env1[$param] = $args[$pos];
                    }
                    return $this->interp1($body, $env1, $ctx);
                }
            };
        });
    }

    private function interpCall($closure, $args, $env, $ctx)
    {
        return $this->interp1($closure, $env, function($callee) use($env, $ctx, $args) {
            abort(is_callable($callee), $callee);
            return $this->interpArgs($args, $env, function($args) use($ctx, $callee) {
                // 接受延续，使用闭包保存
                $callee = $callee($ctx);
                if ($args === null) {
                    return $callee();
                } else {
                    return $callee(...$args);
                }
            });
        });
    }

    private function interpArgs(array $args, $env, $ctx)
    {
        if (empty($args)) {
            return $ctx([]);
        } else {
            $arg0 = $args[0];
            $args = array_slice($args, 1);
            return $this->interp1($arg0, $env, function($arg0) use($args, $env, $ctx) {
                if (empty($args)) {
                    return $ctx([$arg0]);
                } else {
                    return $this->interpArgs($args, $env, function($args) use($arg0, $ctx) {
                        array_unshift($args, $arg0);
                        return $ctx($args);
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
                    abort(count($args) >= $arity, [$arity, $args]);
                }
                return $k($f(...$args));
            };
        };
    }
}