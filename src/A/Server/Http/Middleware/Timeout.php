<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/3/5
 * Time: 下午3:55
 */

namespace Minimalism\A\Server\Http\Middleware;


use Minimalism\A\Server\Http\Context;
use Minimalism\A\Server\Http\Contract\Middleware;

class Timeout implements Middleware
{
    public $timeout;

    public function __construct($timeout)
    {

    }

    public function __invoke(Context $ctx, $next)
    {
        // TODO: Implement __invoke() method.
    }
}