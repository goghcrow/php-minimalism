<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/11
 * Time: 上午12:09
 */

namespace Minimalism\Test\Interpret;



use Minimalism\Async\Interpret\Interpreter;

require __DIR__ . "/../../src/Interpret/Keywords.php";
require __DIR__ . "/../../src/Interpret/Scope.php";
require __DIR__ . "/../../src/Interpret/Interpreter.php";


function interp($ast)
{
    ini_set("xdebug.max_nesting_level", PHP_INT_MAX);
    return (new Interpreter())->interp($ast);
}

function q($x)
{
    return ["quote", $x];
}

interp(["seq",
    ["define", "cons",
        ["fun", ["x", "y"],
            ["fun", ["m"],
                ["m", "x", "y"]]]],

    ["define", "car",
        ["fun", ["z"],
            ["z",
                ["fun", ["p", "q"], "p"]]]],

    ["define", "cdr",
        ["fun", ["z"],
            ["z",
                ["fun", ["p", "q"], "q"]]]],

    ["echo",
        ["car",
            ["cons",
                q(1),
                q(2)]],],

    ["echo", q("\n")],

    ["echo",
        ["cdr",
            ["cons",
                ["quote", 1],
                ["quote", 2]]],],

    ["echo", ["quote", "\n"]],

    ["echo",
        ["car",
            ["cdr",
                ["cons",
                    q(1),
                    ["cons",
                        q(2),
                        q(3)]]]]],

    ["echo", ["quote", "\n"]],
]);
