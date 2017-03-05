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
use Minimalism\A\Server\Http\Exception\HttpException;
use Minimalism\A\Server\Http\Util\Template;


/**
 * Class ExceptionHandler
 * @package Minimalism\A\Server\Http\Middleware
 *
 * TOOD 与 Context 的errorHandler冲突
 */
class ExceptionHandler implements Middleware
{
    public function __invoke(Context $ctx, $next)
    {
        try {
            // 需要在 业务逻辑中间件 前use
            // catch 在 ExceptionHandler 之后use的任何一个中间件的错误
            yield $next;

        } catch (\Exception $ex) {

            $status = 500;
            $code = $ex->getCode() ?: 0;
            $msg = "Internal Error";

            if ($ex instanceof HttpException) {
                $status = $ex->status;
                if ($ex->expose) {
                    $msg = $ex->getMessage();
                }
            }
            // else if ($ex instanceof otherException) { }

            $err = [ "code" => $code,  "msg" => $msg ];

            if ($ctx->accept("json")) {
                $ctx->status = 200;
                $ctx->body = $err;
            } else {
                $ctx->status = $status;

                if ($status === 404) {
                    $ctx->body = (yield Template::render(__DIR__ . "/404.html"));
                } else if ($status === 500) {
                    // 触发错误事件
                    $ctx->app->emit("error", $ctx, $ex);
                    $ctx->body = (yield Template::render(__DIR__ . "/500.html", $err));
                } else {
                    $ctx->body = (yield Template::render(__DIR__ . "/error.html", $err));
                }
            }
        }
    }
}