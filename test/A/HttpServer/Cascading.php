<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/28
 * Time: 上午1:21
 */

use Minimalism\A\Server\Http\Application;
use Minimalism\A\Server\Http\Context;
use Minimalism\A\Server\Http\Middleware\Body;
use Minimalism\A\Server\Http\Middleware\Favicon;
use Minimalism\A\Server\Http\Middleware\Logger;
use Minimalism\A\Server\Http\Middleware\NotFound;
use Minimalism\A\Server\Http\Middleware\ResponseTime;
use function Minimalism\A\Server\Http\sys_echo;

require __DIR__ . "/../../../vendor/autoload.php";


$app = new Application();

$app->uze(new Logger());
$app->uze(new ResponseTime());

$app->uze(new Body());
$app->uze(new NotFound());

$app->uze(function($ctx, $next) {
    throw new \Exception();
    yield;
});

$app->uze(function(Context $ctx) {
    echo "XXXXXXXXXXX\n";
    $ctx->status = 200;
    $ctx->body = "<h1>Hello World</h1>";
});

$app->listen(3000);