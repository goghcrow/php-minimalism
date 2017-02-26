<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/26
 * Time: 上午1:34
 */

namespace Minimalism\A\Server\Http;


class Context
{
    /** @var Application */
    public $app;

    /** @var Request */
    public $req;

    /** @var Response */
    public $res;

    public $body;

    public $code;
}