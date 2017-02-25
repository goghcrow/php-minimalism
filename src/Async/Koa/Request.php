<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/26
 * Time: 上午1:34
 */

namespace Minimalism\Async\Koa;


class Request extends \swoole_http_request
{
    /** @var Application */
    public $app;
    /** @var Request */
    public $req;
    /** @var Response */
    public $res;
    /** @var Context */
    public $ctx;


//    public $method;
//    public $uri;
}