<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/3/1
 * Time: 下午11:48
 */

namespace Minimalism\A\Server\Http\Contract;


use Minimalism\A\Server\Http\Context;

interface Body
{
    public function __invoke(Context $ctx);
}