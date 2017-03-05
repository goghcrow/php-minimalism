<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/3/5
 * Time: 下午3:55
 */

namespace Minimalism\A\Server\Http\Middleware;


use function Minimalism\A\Core\callcc;
use function Minimalism\A\Core\race;
use Minimalism\A\Server\Http\Context;
use Minimalism\A\Server\Http\Contract\Middleware;
use Minimalism\A\Server\Http\Exception\HttpException;

class Timeout implements Middleware
{
    public $timeout;
    public $exception;

    private $timerId;

    public function __construct($timeout, \Exception $ex = null)
    {
        $this->timeout = $timeout;
        if ($ex === null) {
            $this->exception = new HttpException(408, "Request timeout");
        } else {
            $this->exception = $ex;
        }
    }

    public function __invoke(Context $ctx, $next)
    {
        yield race([
            callcc(function($k) {
                $this->timerId = swoole_timer_after($this->timeout, function() use($k) {
                    $k(null, $this->exception);
                });
            }),
            function() use ($next){
                yield $next;
                if (swoole_timer_exists($this->timerId)) {
                    swoole_timer_clear($this->timerId);
                }
            },
        ]);
    }
}