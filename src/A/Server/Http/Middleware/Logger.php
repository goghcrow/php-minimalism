<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/3/1
 * Time: 下午8:55
 */

namespace Minimalism\A\Server\Http\Middleware;


use Minimalism\A\Server\Http\Context;
use Minimalism\A\Server\Http\Contract\Middleware;
use function Minimalism\A\Server\Http\sys_echo;


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