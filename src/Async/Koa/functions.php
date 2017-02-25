<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/26
 * Time: 上午1:35
 */

namespace Minimalism\Async\Koa;

/**
 * @param \Closure[] ...$middleware
 * @param Context $ctx
 * @return \Closure
 */
function compose(array $middleware)
{
    return function(Context $ctx, $next = null) use($middleware) {

        if ($next === null) {
            $next = noop();
        }

        /**
         * $middleware : f1, f2, f3, ... fn
         *
         */
        $i = count($middleware);
        while ($i--) {
            assert($middleware[$i] instanceof \Closure); // TypeError
            // scope 绑定Context, 似的\Closure中可以访问Context子类的protected修饰符
            $curr = $middleware[$i]->bindTo($ctx, Context::class);
            $next = $curr($next);
            assert($next instanceof \Generator);  // TypeError
        }

        yield $next;
    };
}


function noop()
{
    yield;
}

function sys_echo($context) {
    $time = date("Y-m-d H:i:s", time());
    echo "[$time] $context\n";
}

function sys_error($context) {
    $time = date("Y-m-d H:i:s", time());
    fprintf(STDERR, "[$time] $context\n");
}
