<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/26
 * Time: 上午1:33
 */

namespace Minimalism\A\Server\Http;


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

    public $swHttpRes;

    public function __construct(\swoole_http_response $swHttpRes)
    {
        $this->swHttpRes = $swHttpRes;
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