<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/26
 * Time: 上午1:33
 */

namespace Minimalism\A\Server\Http;


/**
 * Class Response
 * @package Minimalism\A\Server\Http
 * @property array $cookie
 * @property array $header
 * @property int $fd
 * @method bool cookie(string $name, string $value = null, int $expires = null, string $path = null, string $domain = null, bool $secure = null, bool $httponly = null)
 * @method bool rawcookie(string $name, string $value = null, int $expires = null, string $path = null, string $domain = null, bool $secure = null, bool $httponly = null)
 * @method bool status(int $http_code)
 * @method bool gzip(int $compress_level = null)
 * @method bool header(string $key, string $value)
 * @method bool write(string $content)
 * @method bool end(string $content = null)
 * @method bool sendfile(string $filename, int $offset = null)
 */
class Response
{
    /* @var Application */
    public $app;

    /** @var \swoole_http_request */
    public $req;

    /** @var \swoole_http_response */
    public $res;

    /** @var Context */
    public $ctx;

    /** @var Request */
    public $request;

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