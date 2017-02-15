<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/5
 * Time: 下午10:23
 */

namespace Minimalism\Test\Interpret;



use Minimalism\Async\Interpret\Interpreter;
use Minimalism\Scheme\Interpreter as YInterp;

require __DIR__ . "/../../src/Interpret/Keywords.php";
require __DIR__ . "/../../src/Interpret/Scope.php";
require __DIR__ . "/../../src/Interpret/Interpreter.php";
require __DIR__ . "/../../src/PhpYin/vendor/autoload.php";

function interp($ast)
{
    return (new Interpreter())->interp($ast);
}

$sexp = <<<'SEXP'
(define say-hello
(fun (who)
    (if (empty who)
      "Hello World"
      (string-append "Hello " who))))

(echo (say-hello null))
(echo (say-hello "chuxiaofeng"))
SEXP;

file_put_contents("tmp", $sexp);
register_shutdown_function(function() { @unlink("tmp"); });


// todo 处理引用递归
function pretty_print($exp, $dep = 0)
{
    if (is_array($exp)) {
        $isNumArr = array_keys($exp) === range(0, count($exp) - 1);
        $arr = [];
        foreach ($exp as $key => $value) {
            if ($isNumArr) {
                $arr[] = pretty_print($value, $dep + 1);
            } else {
                $arr[] = "'$key' => " . pretty_print($value, $dep + 1);
            }
        }
        if ($dep === 0) {
            return "[" . implode(", ", $arr) . "]";
        } else {
            $offset = str_repeat(" ", $dep);
            return "\n{$offset}[" . implode(", ", $arr) . "]";
        }
    } else {
        return var_export($exp, true);
    }
}


$interp = new YInterp();
$ast = $interp->__toAst("tmp");
echo pretty_print($ast), "\n\n";
$r = interp($ast);
// var_dump($r);