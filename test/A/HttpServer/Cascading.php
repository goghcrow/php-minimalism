<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/28
 * Time: 上午1:21
 */

use Minimalism\A\Server\Http\Application;
use Minimalism\A\Server\Http\Context;
use Minimalism\A\Server\Http\Middleware\BodyBaking;
use Minimalism\A\Server\Http\Middleware\ExceptionHandler;
use Minimalism\A\Server\Http\Middleware\Favicon;
use Minimalism\A\Server\Http\Middleware\Logger;
use Minimalism\A\Server\Http\Middleware\NotFound;
use Minimalism\A\Server\Http\Middleware\ResponseTime;
use function Minimalism\A\Server\Http\sys_echo;
use function Minimalism\A\Server\Http\sys_error;

require __DIR__ . "/../../../vendor/autoload.php";



function υse()
{

}


// TODO 实现一个超时 middleware

$app = new Application();
$app->υse(new Logger());
$app->υse(new ResponseTime());
$app->υse(new Favicon(__DIR__ . "/favicon.iRco"));
$app->υse(new BodyBaking()); // 输出特定格式body
$app->υse(new ExceptionHandler()); // 处理异常
$app->υse(new NotFound()); // 处理404

//$app->υse(function($ctx, $next) {
//    echo "before ex\n";
//    throw new \Exception();
//    // yield $next;
//    echo "after ex\n";
//    yield $next;
//});

$app->υse(function(Context $ctx) {
    $ctx->status = 200;
    $ctx->state["title"] = "HELLO WORLD";
    $ctx->state["time"] = date("Y-m-d H:i:s", time());;
    $ctx->state["table"] = $_SERVER;
    yield $ctx->render(__DIR__ . "/index.html");
});


$app->listen(3000);