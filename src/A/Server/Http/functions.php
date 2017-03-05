<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/26
 * Time: 上午1:35
 */

namespace Minimalism\A\Server\Http;


/**
 * @param \Closure[] ...$middleware
 * @return \Closure
 */
function compose(array $middleware)
{
    /**
     * @param Context $ctx
     * @param \Generator $next
     * @return \Generator
     */
    return function(Context $ctx = null, $next = null) use($middleware) {
        if ($ctx === null) {
            $ctx = new Context();
        }
        if ($next === null) {
            $next = noop();
        }

        $i = count($middleware);
        while ($i--) {
            if ($middleware[$i] instanceof \Closure) {
                // scope 绑定Context, 似的\Closure中可以访问Context子类的protected修饰符
                $curr = $middleware[$i]->bindTo($ctx, Context::class);
            } else {
                $curr = $middleware[$i];
            }
            assert(is_callable($curr));

            $next = $curr($ctx, $next);
        }

        yield $next;
    };
}

function noop()
{
    yield;
}

function defer(callable $fn)
{
    return swoole_event_defer($fn);
}

function sys_echo($context) {
    // $_SERVER 会被swoole setglobal 清空, 这里用 $_ENV
    $workerId = isset($_ENV["WORKER_ID"]) ? $_ENV["WORKER_ID"] : "";
    $time = date("Y-m-d H:i:s", time());
    echo "[$time #$workerId] $context\n";
}

function sys_error($context) {
    $workerId = isset($_ENV["WORKER_ID"]) ? $_ENV["WORKER_ID"] : "";
    $time = date("Y-m-d H:i:s", time());
    fprintf(STDERR, "[$time #$workerId] $context\n");
}