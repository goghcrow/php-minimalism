<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/3/1
 * Time: 下午9:14
 */


namespace Minimalism\A\Server\Http\Middleware;


use Minimalism\A\Server\Http\Context;
use Minimalism\A\Server\Http\Contract\Middleware;


/**
 * Class NotFound
 * @package Minimalism\A\Server\Http\Middleware
 *
 * 处理 404 状态码
 */
class NotFound implements Middleware
{

    public function __invoke(Context $ctx, $next)
    {
        yield $next;

        if ($ctx->status !== 404 || $ctx->body) {
            return;
        }

        $ctx->status = 404;

        if ($ctx->accept("json")) {
            $ctx->body = [
                "message" => "Not Found",
            ];
            return;
        }

        // TODO redirect predefined url

        $ctx->body = "<h1>404 Not Found</h1>";
    }
}