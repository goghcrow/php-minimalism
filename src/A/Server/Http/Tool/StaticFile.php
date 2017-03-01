<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/3/1
 * Time: 下午10:15
 */

namespace Minimalism\A\Server\Http\Tool;


use Minimalism\A\Server\Http\Context;
use Minimalism\A\Server\Http\Contract\Body;

class StaticFile implements Body
{
    public $path;
    public $maxAge;
    public $type;

    public function __construct(Context $ctx, $path, $type, $maxAge = 86400000)
    {
        $ctx->lazyBody = true;
        $this->path = $path;
        $this->type = $type;
        $this->maxAge = $maxAge;
    }

    public function __invoke(Context $ctx)
    {
        $ctx->type = $this->type; // swoole sendfile 必须手动指定 content-type
        $ctx->header("Cache-Control", "public, max-age={$this->maxAge}");
        if (is_readable($this->path)) {
            $ctx->status = 200;
            $ctx->sendfile($this->path);
        } else {
            $ctx->status = 404;
        }
        $ctx->body = null;
    }
}