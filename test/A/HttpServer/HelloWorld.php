<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/28
 * Time: 上午1:17
 */

use Minimalism\A\Server\Http\Application;
use Minimalism\A\Server\Http\Context;

require __DIR__ . "/../../../vendor/autoload.php";




function compose(...$funs)
{
    return function($next = null) use($funs) {
        if ($next === null) {
            $next = function() { };
        }

        $i = count($funs);
        while ($i--) {
            $curr = $funs[$i];
            $next = function() use($curr, $next)  {
                $curr($next);
            };
        }

        return $next();
    };
}


// 统计时间
function a($next)
{
    $start = microtime(true);
    echo "before a\n";
    $next();
    echo "after a\n";
    echo "cost: ",  microtime(true) - $start, "\n";
}

// 验证密码
function b($next)
{
    echo "before b\n";
    $next();
    echo "after b\n";
}

// 业务逻辑
function c()
{
    echo "hello c\n";
}

$fn = compose("a", "b", "c");
$fn();
exit;




exit;


$app = new Application();
$app->uze(function(Context $ctx) {
    $ctx->body = "Hello World";
});
$app->listen(3000);