<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/5
 * Time: 下午10:19
 */

namespace Minimalism\Test\Interpret;



use Minimalism\Async\Interpret\Interpreter;

require __DIR__ . "/../../src/Interpret/Constants.php";
require __DIR__ . "/../../src/Interpret/Scope.php";
require __DIR__ . "/../../src/Interpret/Interpreter.php";


function interp($ast)
{
    return (new Interpreter())->interp($ast);
}



function testVar()
{
    $r = interp("true");
    assert($r === true);
    $r = interp("foo");
    assert($r === null);
}
testVar();


function testSeq()
{
    $r = interp(["seq", ["quote", 1], ["quote", 2], ["quote", 3]]);
    assert($r === 3);

    $block = ["seq", ["quote", 1], ["quote", 42]];
    $r = interp(["seq", ["quote", 1], $block]);
    assert($r === 42);
}
testSeq();



function testScope()
{
    $r = interp(
        ["seq",
            ["define", "a", ["quote", 1]],
            "a"
        ]
    );
    assert($r === 1);

    $r = interp(
        ["seq",
            ["seq", ["define", "a", ["quote", 1]]],
            "a"
        ]
    );
    assert($r === null);
}
testScope();



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
    $r = interp(["if", "true",
        ["quote", "TRUE"],
        ["quote", "FALSE"]]);
    assert($r === "TRUE");

    $r = interp(["if", "false",
        ["quote", "TRUE"],
        ["quote", "FALSE"]]);
    assert($r === "FALSE");

    ob_start();
    $r = interp(
        ["if",
            [">",
                ["quote", 1],
                ["quote", 2]],
            ["quote", "1 > 2"],
            ["quote", "1 < 2"]]);
    assert($r === "1 < 2");
}
testIf();




// define 就是个语法糖
function testDef()
{
//     (define var hello)
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
                ["string-append",
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
    assert(ob_get_clean() === "1");
    assert($r === 2);
}
testCallCC();



function testCurry()
{
    $fun =
        ["fun",
            ["a", "b", "c"],
            ["seq",
                ["quote", "nothing"],
                ["quote", "nothing"],
                ["+", ["+", "a", "b"], "c"]
            ]];
    $add1 = [$fun, ["quote", 1]];
    $add12 = [$add1, ["quote", 2]];
    $call = [$add12, ["quote", 7]];

    $r = interp($call);
    assert($r === 10);
}
testCurry();



function testClosure()
{
    $upValue = ["define", "a", ["quote", 100]];
    $fun =
        ["fun",
            ["b", "c"],
            ["+", ["+", "a", "b"], "c"]];
    $call = [$fun, ["quote", 1], ["quote", 2]];
    $seq = ["seq", $upValue, $fun, $call];
    $r = interp($seq);
    assert($r === 103);
}
testClosure();



/*
$ast = [
    "seq",

    ["define", "test",
        ["fun", ["arg"],
            ["if", [">", ["quote", 1], ["quote", 0]],
                ["echo", "arg"],
                ["echo", ["quote", "1 <= 0"]]]]],

    ["test", ["quote", "hello"]]
];

interp($ast);
*/