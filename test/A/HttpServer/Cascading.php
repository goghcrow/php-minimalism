<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/28
 * Time: 上午1:21
 */

namespace Minimalism\Test\A\HttpServer;

use function Minimalism\A\Client\async_sleep;
use Minimalism\A\Server\Http\Application;
use Minimalism\A\Server\Http\Context;
use Minimalism\A\Server\Http\Exception\HttpException;
use Minimalism\A\Server\Http\Middleware\BodyBaking;
use Minimalism\A\Server\Http\Middleware\ExceptionHandler;
use Minimalism\A\Server\Http\Middleware\Favicon;
use Minimalism\A\Server\Http\Middleware\Logger;
use Minimalism\A\Server\Http\Middleware\NotFound;
use Minimalism\A\Server\Http\Middleware\ResponseTime;
use Minimalism\A\Server\Http\Middleware\Timeout;
use function Minimalism\A\Server\Http\sys_echo;
use function Minimalism\A\Server\Http\sys_error;

require __DIR__ . "/../../../vendor/autoload.php";



$app = new Application();
// $app->silent = false;
$app->on("error", function(Context $ctx, \Exception $ex) {
    sys_error("{$ctx->method} {$ctx->url} " . $ex->getMessage());
});

$app->υse(new Logger());
$app->υse(new ResponseTime());
$app->υse(new Favicon(__DIR__ . "/favicon.iRco"));
$app->υse(new BodyBaking()); // 输出特定格式body
$app->υse(new ExceptionHandler()); // 处理异常
$app->υse(new NotFound()); // 处理404
$app->υse(new Timeout(200)); // 处理请求超时, 会抛出HttpException

//$app->υse(function($ctx, $next) {
//    echo "before ex\n";
//    throw new \Exception();
//    // yield $next;
//    echo "after ex\n";
//    yield $next;
//});

$app->υse(function(Context $ctx) {

    // test1
    // $ctx->status = 404;

    // test2
    $ctx->status = 200;
    $ctx->state["title"] = "HELLO WORLD";
    $ctx->state["time"] = date("Y-m-d H:i:s", time());;
    $ctx->state["table"] = $_SERVER;
    yield $ctx->render(__DIR__ . "/index.html");

    // test
    // $ctx->body = "<pre>" . print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), true);

    // test3 超时
    // yield async_sleep(500);

    // test4
    // 抛出带status的错误
    // $ctx->thrοw(500, "Internal Error");
    // throw new HttpException(500, "Internal Error"); // 相等

    // test5
    // 直接抛出错误, 500 错误
    // throw new \Exception("some internal error", 10000);
});


$app->listen(3000);