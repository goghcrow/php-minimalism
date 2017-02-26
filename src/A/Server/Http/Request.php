<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/26
 * Time: 上午1:34
 */

namespace Minimalism\A\Server\Http;


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

    public $swHttpReq;

    public function __construct(\swoole_http_request $swHttpReq)
    {
        $this->swHttpReq = $swHttpReq;
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->swHttpReq, $name], $arguments);
    }

    public function __get($name)
    {
        return $this->swHttpReq->$name;
    }

    public function __set($name, $value)
    {
        $this->swHttpReq->$name = $value;
    }
}