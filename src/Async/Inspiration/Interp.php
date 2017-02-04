<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/2
 * Time: 下午7:57
 */

namespace Minimalism\Async\Inspiration;


// env 引用类型Array
class Environment implements \ArrayAccess
{
    public $table;

    public function __construct(array $table = [])
    {
        $this->table = $table;
    }

    public function offsetExists($offset)
    {
        return isset($this->table[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->table[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->table[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->table[$offset]);
    }
}




// 如果将一个解释器转换为CPS形式,
// 那么就可以很容易的实现像 scheme中call/cc, let/cc这样可以获得当前continuation的结构,
// 因为解释器的continuation代表的解 释器接下来要进行的计算,
// 而解释器是用来模拟用户程序的, 所以实际上这个 continuation也可以看作是用户程序接下来要完成的计算,
// 也就是用户程序当前的 continuation.
// @see https://typeof.net/2014/m/trailer-from-a-interpreter-to-abstract-interpretation.html
// continuation context ctx k

// 1. TODO ast入口追加seq关键字(seq 函数调用)， 函数body追加seq关键字(seq 函数调用)
// 2. TODO curry 支持
// 3. TODO 理解call/ccc
// 4. TODO 做代码生成


// 词法作用域
// Lambda 抽象
// call/cc
// 有趣的是，这个解释器的框架代码可以沿用到编译器里：
// 从本质上来说，「编译」也是「解释」的一种。

final class Interpreter
{
    /**
     * php fun to cps fun
     * @param callable $f
     * @return \Closure
     */
    public static function defun(callable $f)
    {
        return function($k) use($f) {
            return function(...$args) use($f, $k) {
                return $k($f(...$args));
            };
        };
    }

    public function interp($ast)
    {
        $env = [];
        foreach (get_defined_functions()["internal"] as $f) {
            $env[$f] = self::defun($f);
        }

        $env["#t"] = true;
        $env["#f"] = false;
        $env["seq"] = self::defun(function(...$args) {
            assert(count($args) > 0);
            return end($args);
        });
        $env["+"] = self::defun(function(...$args) {
            assert(count($args) === 2);
            return $args[0] + $args[1];
        });
        $env["-"] = self::defun(function(...$args) {
            assert(count($args) === 2);
            return $args[0] - $args[1];
        });
        $env["*"] = self::defun(function(...$args) {
            assert(count($args) === 2);
            return $args[0] * $args[1];
        });
        $env["/"] = self::defun(function(...$args) {
            assert(count($args) === 2);
            return $args[0] / $args[1];
        });
        $env["and"] = self::defun(function(...$args) {
            assert(count($args) === 2);
            return $args[0] and $args[1];
        });
        $env["or"] = self::defun(function(...$args) {
            assert(count($args) === 2);
            return $args[0] or $args[1];
        });
        $env["not"] = self::defun(function(...$args) {
            assert(count($args) === 1);
            return !$args[0];
        });
        $env["echo"] = self::defun(function(...$args) {
            assert(count($args) === 1);
            echo $args[0];
        });
        $env["."] = self::defun(function(...$args) {
            assert(count($args) === 2);
            return $args[0] . $args[1];
        });

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


        $id = function() {
            assert(func_num_args() === 1);
            return func_get_arg(0);
        };

        return $this->interp1($ast, new Environment($env), $id);
    }

    /**
     * cps interp
     * @param $ast
     * @param Environment $env
     * @param callable $k k is the continuation
     * @return mixed
     */
    public function interp1($ast, Environment $env, $k)
    {
        if (is_array($ast)) {
            assert(!empty($ast));
            switch ($ast[0]) {
                case "define":
                    assert(count($ast) === 3);
                    list(, $name, $value) = $ast;
                    assert(is_string($name) && strlen($name) > 0);
                    return $this->interp1($value, $env, function($v) use($k, $name, $env) {
                        $env[$name] = $v;
                        return $k(null);
                    });

                case "fun":
                    assert(count($ast) === 3);
                    list(, $params, $body) = $ast;
                    return $k(function($k) use($params, $body, $env) { // fun 在 call时候才会接收到continuation $k, 对应 $callee = $callee($k);
                        return function(...$args) use($params, $body, $env, $k) {
                            $env1 = clone $env;
                            // todo 检查参数个数与curry
                            foreach ($params as $pos => $param) {
                                $env1[$param] = $args[$pos];
                            }
                            return $this->interp1($body, $env1, $k);
                        };
                    });

                case "if":
                    assert(count($ast) === 4);
                    list(, $test, $then, $else) = $ast;
                    return $this->interp1($test, $env, function($v) use($then, $else, $k, $env) {
                        if ($v) {
                            return $this->interp1($then, $env, $k);
                        } else {
                            return $this->interp1($else, $env, $k);
                        }
                    });

                case "call/cc":
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

                case "quote";
                    assert(count($ast) === 2);
                    return $k($ast[1]);

                default /*call*/:
                    assert(count($ast) > 0);
                    $fun = $ast[0];
                    $args = array_slice($ast, 1);
                    return $this->interp1($fun, $env, function($callee) use($env, $k, $args) {
                        assert(is_callable($callee));
                        return $this->interpArgs($args, $env, function($args) use($k, $callee) {
                            // TODO 注释
                            $callee = $callee($k);
                            if ($args === null) {
                                return $callee();
                            } else {
                                return $callee(...$args);
                            }
                        });
                    });
            }
        } else if (is_string($ast)) {
            $name = $ast;
            return $k(isset($env[$name]) ? $env[$name] : null);
        } else {
            return $k($ast);
        }
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
}






function interp($ast)
{
    return (new Interpreter())->interp($ast);
}


function testVar()
{
    $r = interp("#t");
    assert($r === true);
    $r = interp("foo");
    assert($r === null);
}
testVar();


function testScope()
{

}


function testQuote()
{
    $r = interp(["quote", "hello world\n"]);
    assert($r == "hello world\n");

    $exp = range(1, 10);
    $r = interp(["quote", $exp]);
    assert($r == $exp);
}
testQuote();



function testIf()
{
    $r = interp(["if", "#t", ["quote", "TRUE"], ["quote", "FALSE"]]);
    assert($r === "TRUE");
    $r = interp(["if", "#f", ["quote", "TRUE"], ["quote", "FALSE"]]);
    assert($r === "FALSE");
}
testIf();

function testSeq()
{
    $r = interp(["seq", ["quote", 1], ["quote", 2], ["quote", 3]]);
    assert($r === 3);
}
testSeq();


// define 就是个语法糖
function testDef()
{
    // (define var hello)
    $fun = ["fun", ["var"], null];
    $call = [$fun, "hello"];
    $r = interp($call);
    assert($r === null);


    $fun = ["fun",
        ["arg1"],
        [
            "seq",
            ["*",
                [["fun", ["arg2"], "arg2"], ["quote", 42]],
                "arg1"]
        ]];
    $call = [$fun, ["quote", 2]];
    $r = interp($call);
    assert($r === 84);



    $ast = [
        "seq",
        ["define", "foo", ["quote", "bar"]],
        [["fun", [], "foo"]] // 词法作用域
    ];
    $r = interp($ast);
    assert($r === "bar");


    $ast = [
        "seq",
        ["define", "hello",
            ["fun", ["name"],
                [".",
                    ["quote", "hello "],
                    "name"]]],
        ["hello", ["quote", "xiaofeng"]]
    ];
    $r = interp($ast);
    assert($r === "hello xiaofeng");
}
testDef();




function testFun()
{
    $emptyFun = ["fun", [], null];


    $funCall = [["fun", [], ["quote", "return"]]];
    $r = interp($funCall);
    assert($r === "return");

    $fun =
        ["fun",
            ["a", "b", "c"],
            ["+", ["+", "a", "b"], "c"]];
    $call = [$fun, ["quote", 1], ["quote", 2], ["quote", 3]];
    $r = interp($call);
    assert($r === 6);


    $fun =
        ["fun",
            ["a", "b", "c"],
            ["seq",
                ["quote", "nothing"],
                ["quote", "nothing"],
                ["+", ["+", "a", "b"], "c"]
            ]];
    $call = [$fun, ["quote", 1], ["quote", 2], ["quote", 3]];
    $r = interp($call);
    assert($r === 6);

}
testFun();


function testCall()
{

    $ast = ["echo",
            ["quote", "hello world\n"]];

    ob_start();
    $r = interp($ast);
    assert($r === null);
    assert(ob_get_clean() === "hello world\n");

    ob_start();
    $ast = ["printf",
            ["quote", "%s\n"],
            ["quote", "hello world"]];
    $r = interp($ast);
    assert($r === strlen("hello world\n"));
    assert(ob_get_clean() === "hello world\n");
}
testCall();



function testCallCC()
{
    $ast =
        ['call/cc',
            ['fun',
                ['return'],
                ['seq',
                    ['echo', ['quote', 1]],
                    ['return', ['quote', 2]],
                    ['echo', ['quote', 3]],
                ]]];

    ob_start();
    $r = interp($ast);
    var_dump(ob_get_clean() === "1");
    assert($r === 2);
}
testCallCC();