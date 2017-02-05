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

require __DIR__ . "/../../src/Interpret/Constants.php";
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
(echo "\n")
(echo (say-hello "chuxiaofeng"))
SEXP;

file_put_contents("tmp", $sexp);
register_shutdown_function(function() { @unlink("tmp"); });


$interp = new YInterp();
$ast = $interp->__toAst("tmp");
//echo json_encode($ast, JSON_PRETTY_PRINT), "\n";
$r = interp($ast);
//// var_dump($r);