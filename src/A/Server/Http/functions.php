<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/26
 * Time: 上午1:35
 */

namespace Minimalism\A\Server\Http;


// middleware 单复同形

function compose(array $middleware)
{
    assert(!empty($middleware));

    return function(Context $ctx = null) use($middleware) {
        if ($ctx === null) {
            $ctx = new Context();
        }

        $next = null;
        // rightReduce
        $middleware = array_reverse($middleware, true);
        return array_reduce($middleware, function($rightNext, $leftFn) use($ctx) {
            if ($leftFn instanceof \Closure) {
                $leftFn = $leftFn->bindTo($ctx, Context::class);
            }
            $task = $leftFn($ctx, $rightNext);
            assert($task instanceof \Generator);
            return $task;
        }, $next);
    };
}


/**
 * @param \Closure[] $middleware
 * @return \Closure
 */
function compose1(array $middleware)
{
    assert(!empty($middleware));

    /**
     * @param Context $ctx
     * @return \Generator
     * TODO 其实可以把ctx的属性附加到next的Generator对象上, 这样只有一个参数了~
     * TODO 现在函数每次调用都需要 rightReduce 一次, 考虑能否移到闭包外面
     */
    return function(Context $ctx = null/*, $next = null*/) use($middleware) {
        if ($ctx === null) {
            $ctx = new Context();
        }
        /* if ($next === null) { $next = function() { yield; }; } */
        $next = null;

        $i = count($middleware);
        while ($i--) {
            if ($middleware[$i] instanceof \Closure) {
                // scope 绑定Context, 似的\Closure中可以访问Context子类的protected修饰符
                $curr = $middleware[$i]->bindTo($ctx, Context::class);
            } else {
                $curr = $middleware[$i];
            }

            // TODO
            // $curr = \Minimalism\A\Core\await($curr);
            assert(is_callable($curr));

            $next = $curr($ctx, $next);
            // 中间件必须是\Generator, 否则compose阶段会先执行, 影响中间件顺序
            assert($next instanceof \Generator);
        }

        return $next;
    };
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