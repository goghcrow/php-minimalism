<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/26
 * Time: 上午1:33
 */

namespace Minimalism\Async\Koa;


class Response extends \swoole_http_response
{
    /* @var Application */
    public $app;
    /** @var Request */
    public $req;
    /** @var Response */
    public $res;
    /** @var Context */
    public $ctx;

    public $header;
    public $body;
}