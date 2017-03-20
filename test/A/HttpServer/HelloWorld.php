<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/28
 * Time: 上午1:17
 */

namespace Minimalism\Test\A\HttpServer;


use Minimalism\A\Server\Http\Application;
use Minimalism\A\Server\Http\Context;
use function Minimalism\A\Server\Http\sys_echo;
use function Minimalism\A\Server\Http\sys_error;

require __DIR__ . "/../../../vendor/autoload.php";

$app = new Application();

$app->υse(function(Context $ctx, $next) {
    $start = microtime(true);
    yield $next;
    $ms = number_format(microtime(true) - $start, 7);
    // response header 写入 X-Response-Time: xxxms
    $ctx->{"X-Response-Time"} = "{$ms}ms";
    sys_echo("$ctx->method $ctx->url - $ms");
});

$app->υse(function(Context $ctx) {
    $ctx->status = 200;
    $ctx->body = "<h1>Hello World</h1>";
});
$app->listen(3000);