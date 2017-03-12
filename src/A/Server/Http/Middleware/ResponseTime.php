<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/3/1
 * Time: 下午9:12
 */

namespace Minimalism\A\Server\Http\Middleware;


use Minimalism\A\Server\Http\Context;
use Minimalism\A\Server\Http\Contract\Middleware;


class ResponseTime implements Middleware
{
    public function __invoke(Context $ctx, $next)
    {
        $start = microtime(true);

        yield $next;

        $ms = ceil(microtime(true) - $start);
        $ctx->header("X-Response-Time", "{$ms}ms");
    }
}