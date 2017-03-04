<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/3/1
 * Time: 下午10:06
 */

namespace Minimalism\A\Server\Http\Middleware;


use Minimalism\A\Server\Http\Context;
use Minimalism\A\Server\Http\Contract\Middleware;
use Minimalism\A\Server\Http\Tool\StaticFile;

// TODO 实现的不大对。。。。
class Favicon implements Middleware
{
    public $path;

    public function __construct($path)
    {
        $this->path = $path;
    }

    public function __invoke(Context $ctx, $next)
    {
        if ($ctx->path === "/favicon.ico") {
            if ($ctx->method !== "GET" && $ctx->method !== "HEAD") {
                $ctx->status = $ctx->method === "OPTIONS" ? 200 : 405;
                $ctx->header('Allow', 'GET, HEAD, OPTIONS');
            } else {
                $ctx->status = 200;
                $ctx->type = "image/x-icon"; // swoole sendfile 必须手动指定 content-type
                $ctx->header("Cache-Control", "public, max-age=86400000");
                if (is_readable($this->path)) {
                    $ctx->status = 200;
                    $ctx->sendfile($this->path);
                } else {
                    $ctx->status = 404;
                }
                $ctx->body = null;
                // $ctx->body = new StaticFile($ctx, $this->path, "image/x-icon");
            }
        } else {
            yield $next;
        }
    }
}