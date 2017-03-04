<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/3/4
 * Time: 上午1:59
 */

namespace Minimalism\A\Server\Http\Middleware;


use Minimalism\A\Server\Http\Context;
use Minimalism\A\Server\Http\Contract\Middleware;


class ExceptionHandler implements Middleware
{
    public function __invoke(Context $ctx, $next)
    {
        try {
            // 需要在 业务逻辑中间件 前use
            // catch 在 ExceptionHandler 之后use的任何一个中间件的错误
            yield $next;
        } catch (\Exception $ex) {
            $code = $ex->getCode() ?: 500;
            $msg = $ex->getMessage();

            /*
            if ($ex instanceof FooException) {

            } else if ($ex instanceof BarException) {

            } ...
            */

            if ($ctx->accept("json")) {
                $ctx->status = 200;
                $ctx->body = [
                    "code" => $code,
                    "msg" => $msg === null ? "网络错误" : $msg,
                ];
            } else {
                $ctx->status = $code;
                // switch ($code) { }
                // $ctx->body =
                // TODO
                yield $ctx->render("$code.html");
            }
        }
    }
}