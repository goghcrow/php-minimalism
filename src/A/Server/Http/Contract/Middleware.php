<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/28
 * Time: 上午2:37
 */

namespace Minimalism\A\Server\Http\Contract;


use Minimalism\A\Server\Http\Context;

interface Middleware
{
    public function __invoke(Context $ctx, $next);
}