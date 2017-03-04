<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/3/1
 * Time: 下午10:27
 */

namespace Minimalism\A\Server\Http\Middleware;


use Minimalism\A\Server\Http\Context;
use Minimalism\A\Server\Http\Contract\Middleware;

class BodyBaking implements Middleware
{
    public function __invoke(Context $ctx, $next)
    {
        yield $next;

        $body = $ctx->body;

        // for function __invoke(Context $ctx);
        // $body instanceof Body 不够灵活
        if ($ctx->lazyBody && is_callable($body)) {
            $ctx->body = $body($ctx);
        }

        if (is_array($ctx->body)) {
            $ctx->body = json_encode($ctx->body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $ctx->res->header("Content-Type", "application/json");
        }
    }
}