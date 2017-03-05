<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/26
 * Time: 上午1:33
 */

namespace Minimalism\A\Server\Http;
use function Minimalism\A\Client\async_read;
use Minimalism\A\Server\Http\Tool\Template;


/**
 * Class Response
 * @package Minimalism\A\Server\Http
 *
 * getter @property array $cookie
 * getter @property array $header
 * getter @property int $fd
 *
 * setter @property string $type
 * setter @property int $lastModified
 * setter @property string $etag
 * setter @property int $length
 *
 * @method bool cookie(string $name, string $value = null, int $expires = null, string $path = null, string $domain = null, bool $secure = null, bool $httponly = null)
 * @method bool rawcookie(string $name, string $value = null, int $expires = null, string $path = null, string $domain = null, bool $secure = null, bool $httponly = null)
 * @method bool status(int $http_code)
 * @method bool gzip(int $compress_level = null)
 * @method bool header(string $key, string $value)
 * @method bool write(string $content)
 * method bool end(string $content = null)
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

    public $isEnd = false;

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
        /** @var $fn callable */
        $fn = [$this->res, $name];
        return $fn(...$arguments);
    }

    public function __get($name)
    {
        return $this->res->$name;
    }

    public function __set($name, $value)
    {
        switch ($name) {
            case "type":
                return $this->res->header("Content-Type", $value);
            case "lastModified":
                return $this->res->header("Last-Modified", $value);
            case "etag":
                return $this->res->header("ETag", $value);
            case "length":
                return $this->res->header("Content-Length", $value);

            default:
                $this->res->$name = $value;
                return true;
        }
    }

    public function end($html = "")
    {
        if ($this->isEnd) {
            return false;
        }
        $this->isEnd = true;
        return $this->res->end($html);
    }

    public function redirect($url, $status = 302)
    {
        $this->res->header("Location", $url);
        $this->res->header("Content-Type", "text/plain; charset=utf-8");
        $this->ctx->status = $status;
        $this->ctx->body = "Redirecting to $url.";
    }

    public function render($file)
    {
        $this->ctx->body = (yield Template::render($file, $this->ctx->state));
    }
}