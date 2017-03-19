<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/27
 * Time: 上午12:30
 */

namespace Minimalism\Test\A;

use function Minimalism\A\Core\async;
use function Minimalism\A\Core\await;
use function Minimalism\A\Server\Http\compose;
use Minimalism\A\Server\Http\Context;
use Minimalism\A\Server\Http\Middleware\ExceptionHandler;

require __DIR__ . "/../../vendor/autoload.php";


async(function() {
    $task = compose(function(Context $ctx, $next) {
        echo 1;
        /* @var $this Context */
        $this->body = "x";
        yield $next;
        echo 6;
    }, function(Context $ctx, $next) {
        echo 2;
        $this->body .= "y";
        yield $next;
        echo 5;
    }, function(Context $ctx, $next) {
        echo 3;
        $this->body .= "z";
        yield $next;
        echo 4;
    }, function(Context $ctx, $next) {
        echo $this->body;
        yield $next;
    });

    // 123xyz456
    yield await($task);
});



echo "\n";


class MockRes
{
    public function render() {
        // var_dump(func_get_args());
    }
}

$ctx = new Context();
$ctx->response = new MockRes();

// 异常透传, 终止执行
async(function()  use($ctx) {

    yield await(compose(
        function($ctx, $next) {
            echo "{ ";
            yield $next;
            echo " }";
        },
        new ExceptionHandler(function($ex) {}),
        function($ctx, $next) {
            echo "[ ";
            yield $next;
            throw new \Exception();
            echo "] ";
        },
        function($ctx, $next) {
            echo "( ";
            yield $next;
            echo ") ";
        }
    ), $ctx);
});

echo "\n";

// 可通过try catch 保证某个过滤器一下的异常不会继续向上透传
// 异常截获
async(function() use($ctx) {
    yield await(compose(
        function($ctx, $next) {
            echo "{ ";
            try {
                yield $next;
            } catch (\Exception $ex) {

            }
            echo " }";
        },
        function($ctx, $next) {
            echo "[ ";
            yield $next;
            throw new \Exception();
            echo "] ";
        },
        function($ctx, $next) {
            echo "( ";
            yield $next;
            echo ") ";
        }
    ), $ctx);
});