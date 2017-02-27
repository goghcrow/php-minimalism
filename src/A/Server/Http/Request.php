<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/26
 * Time: 上午1:34
 */

namespace Minimalism\A\Server\Http;


/**
 * Class Request
 * @package Minimalism\A\Server\Http
 * @property array $request
 * @property array $cookie
 * @property array $header
 * @property array $server
 * @property string $file
 * @property mixed $post
 * @property int $fd
 * @method string rawcontent()
 *
 */
class Request
{
    /** @var Application */
    public $app;

    /** @var \swoole_http_request */
    public $req;

    /** @var \swoole_http_response */
    public $res;

    /** @var Context */
    public $ctx;

    /** @var Response */
    public $response;

    /** @var string */
    public $originalUrl;

    /** @var string */
    public $ip;

    public function __construct(Application $app, Context $ctx,
                                \swoole_http_request $req, \swoole_http_response $res)
    {
        $this->app = $app;
        $this->ctx = $ctx;
        $this->req = $req;
        $this->res = $res;
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->req, $name], $arguments);
    }

    public function __get($name)
    {
        return $this->req->$name;
    }

    public function __set($name, $value)
    {
        $this->req->$name = $value;
    }
}