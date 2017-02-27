<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/28
 * Time: 上午1:21
 */

use Minimalism\A\Server\Http\Application;
use Minimalism\A\Server\Http\Context;
use Minimalism\A\Server\Http\Middleware;
use function Minimalism\A\Server\Http\sys_echo;
use Minimalism\A\Server\Util\Terminal as T;

require __DIR__ . "/../../../vendor/autoload.php";


$app = new Application();

$app->uze(function(Context $ctx, $next) {
    $start = microtime(true);
    yield $next;
    $ms = microtime(true) - $start;
    $ctx->{"X-Response-Time"} = "{$ms}ms";
});


/*
class Logger implements Middleware
{
    public function __invoke(Context $ctx, $next)
    {
        $start = microtime(true);
        yield $next;
        $ms = number_format(microtime(true) - $start, 7);
        $ctx->{"X-Response-Time"} = "{$ms}ms";
        sys_echo("$ctx->method $ctx->url - $ms");
    }
}
$app->uze(new Logger());
*/


/*
$app->uze(function(Context $ctx, $next) {
    $start = microtime(true);
    yield $next;
    $ms = number_format(microtime(true) - $start, 7);
    $ctx->{"X-Response-Time"} = "{$ms}ms";

    $method = T::format($ctx->method, T::FG_GREEN, T::BRIGHT);
    $url = T::format($ctx->url, T::FG_MAGENTA, T::BRIGHT);
    sys_echo("$method $url - $ms");
});
*/


function logger(Context $ctx, $next)
{
    $start = microtime(true);

    yield $next;

    $ms = number_format(microtime(true) - $start, 7);
    $ctx->{"X-Response-Time"} = "{$ms}ms";
    sys_echo("$ctx->method $ctx->url - $ms");
}

function responseTime(Context $ctx, $next)
{
    $start = microtime(true);

    yield $next;

    $ms = ceil(microtime(true) - $start);
    $ctx->header("X-Response-Time", "{$ms}ms");
}

$app->uze(__NAMESPACE__ . "\\logger");
$app->uze(__NAMESPACE__ . "\\responseTime");
$app->uze(function(Context $ctx) {
    $ctx->status = 200;
    $ctx->body = "Hello World";
});

$app->listen(3000);