<?php

//[["lambda", ["a"], [ "print", "a" ]], "a"]
function interp($expr, array $env = [])
{
    if (is_scalar($expr)) {
        if (is_numeric($expr)) {
            return [floatval($expr), $env];
        } else if ($expr === "true") {
            return [true, $env];
        } else if ($expr === "false") {
            return [true, $env];
        } else if ($expr === "null") {
            return [true, $env];
        } else if (array_key_exists($expr, $env)) {
            return [$env[$expr], $env];
        } else {
            throw new \RuntimeException("ERROR expr: $expr");
        }
    } else if (is_array($expr)) {
        if (empty($expr)) {
            throw new \RuntimeException("ERROR expr []");
        }

        $fn = array_shift($expr);
        $args = $expr;

        if ($fn === "seq") {
            $val = null;
            foreach ($args as $expr) {
                list($val, $env) = interp($expr, $env);
            }
            return [$val, $env];
        } else if ($fn === "define") {
            if (count($args) !== 2) {
                throw new \RuntimeException("ERROR define expr " . print_r($args, true));
            }
            list($var, $defExpr) = $args;
            list($val, $env) = interp($defExpr, $env);
            if (array_key_exists($var, $env)) {
                throw new \RuntimeException("ERROR $var already defined");
            }
            $env[$var] = $val;
            return [null, $env];
        } else if ($fn === "lambda") {
            if (count($args) !== 2) {
                throw new \RuntimeException("ERROR lambda expr " . print_r($args, true));
            }
            list($params, $body) = $args;
            if (!is_array($params)) {
                throw new \RuntimeException("ERROR lambda parameters expr " . print_r($params, true));
            }
            // body 只允许一个表达式, 多个表达式使用 seq
            if (!is_array($body)) {
                throw new \RuntimeException("ERROR lambda body expr " . print_r($body, true));
            }
            $closure = ["closure", $params, $body, $env];
            return [$closure, $env];
        } else if ($fn === "if") {
            if (count($args) !== 3) {
                throw new \RuntimeException("ERROR if expr " . print_r($args, true));
            }
            list($cond, $then, $else) = $args;
            // cond, then, else 产生的环境只在其自身作用域有效
            list($val, ) = interp($cond, $env);
            // if 只有 true 才返回true
            // fixme 修改成php的逻辑?!
            if ($val === true) {
                list($val, ) = interp($then, $env);
            } else {
                list($val, ) = interp($else, $env);
            }
            return [$val, $env];
        } else {
            // apply fn
            // list(list(, $params, $body, $closureEnv), ) = self::eval($fn, $env);
            list($fn, ) = interp($fn, $env);
            if (count($fn) !== 4) {
                throw new \RuntimeException("ERROR fn expr " . print_r($fn, true));
            }

            list($name, $params, $body, $closureEnv) = $fn;

            $argVals = [];
            foreach ($args as $arg) {
                list($argVals[], ) = interp($arg, $env);
            }

            if ($name !== "primitive" && count($params) !== count($argVals)) {
                throw new \RuntimeException("ERROR fn apply expr, parameter: " . print_r($params, true) . ", arguments: " . print_r($argVals, true));
            }
            // build in
            if (is_callable($body)) {
                return $body($argVals, $closureEnv);
            }
            // 注意优先级
            $env = array_merge($env, $closureEnv, array_combine($params, $argVals));
            return interp($body, $env);
        }
    } else {
        throw new \RuntimeException("ERROR expr " . print_r($expr, true));
    }
}


function primitive(callable $fn)
{
    return [
        "primitive", // name
        null, // parameter
        function(array $args = [], array $env = []) use($fn) {
            return [$fn(...$args), $env];
        },
        [] // env
    ];
}


$env = [
    '<' => primitive(function ($a, $b) { return $a < $b; }),
    '>' => primitive(function ($a, $b) { return $a > $b; }),
    '<=' => primitive(function ($a, $b) { return $a <= $b; }),
    '>=' => primitive(function ($a, $b) { return $a >= $b; }),
    '+' => primitive(function ($a, $b) { return $a + $b; }),
    '-' => primitive(function ($a, $b) { return $a - $b; }),
    'not' => primitive(function ($a) { return $a !== true; }),
    'echo' => primitive(function ($a) { echo $a; }),
];


//$code = [
//    'seq',
//    ['define', 'fib',
//        ['lambda', ['n'],
//            ['if', ['<', 'n', '2'],
//                'n',
//                ['+',
//                    ['fib', ['-', 'n', '1']],
//                    ['fib', ['-', 'n', '2']]]]]],
//    ['fib', '12'],
//];
//
//
//function fib($n) {
//    assert($n >= 0);
//    if ($n < 2) {
//        return $n;
//    } else {
//        return fib($n - 1) + fib($n - 2);
//    }
//}
//var_dump(fib(12));
//list($val, ) = interp($code, $env);
//var_dump($val);


//$code = ["seq", ["define", "a", 42], ["echo", "a"]];

function read()
{
    echo "> ";
    return stream_get_line(STDIN, 1024, PHP_EOL);
}

while (null !== ($expr = read())) {
    if ($expr === "") {
        continue;
    }
    try {
        eval("\$expr=$expr;");
        list($val, $env) = interp($expr, $env);
        var_dump($val);
    } catch (\Throwable $t) {
        print_r($expr);
        echo $t->getMessage(), "\n";
    }
}