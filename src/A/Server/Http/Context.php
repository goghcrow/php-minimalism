<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/26
 * Time: 上午1:34
 */

namespace Minimalism\A\Server\Http;


/**
 * Class Context
 * @package Minimalism\A\Server\Http
 *
 * getter @property string rawcontent
 * getter @property array post
 * getter @property array get
 * getter @property string files
 * getter @property array cookie
 * getter @property array cookies
 * getter @property array request
 * getter @property array header
 * getter @property array headers
 * getter @property string url
 * getter @property string originalUrl
 * getter @property string origin
 * getter @property string method
 * getter @property string path
 * getter @property string query
 * getter @property string querystring
 * getter @property string host
 * getter @property string hostname
 * getter @property string protocol
 * getter @property string ip
 *
 * setter @property string $type
 * setter @property int $lastModified
 * setter @property string $etag
 * setter @property int $length
 **
 * @method bool cookie(string $name, string $value = null, int $expires = null, string $path = null, string $domain = null, bool $secure = null, bool $httponly = null)
 * @method bool rawcookie(string $name, string $value = null, int $expires = null, string $path = null, string $domain = null, bool $secure = null, bool $httponly = null)
 * @method bool status(int $http_code)
 * @method bool gzip(int $compress_level = null)
 * @method bool header(string $key, string $value)
 * @method bool write(string $content)
 * @method bool end(string $content = null)
 * @method bool sendfile(string $filename, int $offset = null)
 * @method bool render(string $file)
 *
 * @method void redirect(string $url, int $status = 302)
 */
class Context
{
    /** @var Application */
    public $app;

    /** @var Request */
    public $request;

    /** @var Response */
    public $response;

    /** @var \swoole_http_request */
    public $req;

    /** @var \swoole_http_response */
    public $res;

    /**
     * @var callable onError(\Exception $ex)
     */
    public $onError;

    // TODO
    public $state = [];

    /** @var bool */
    public $respond = true;

    /** @var bool */
    public $lazyBody = false;

    /** @var string */
    public $body;

    /** @var int */
    public $status;

    // todo public $headers ; set() ; append(); response 中一次性设置所有headers

    public function __construct()
    {
        $this->onError = $this->getDefaultErrorHandler();
    }

    public function accept(...$types)
    {
        // TODO
        return false;
    }

    public function __call($name, $arguments)
    {
        /* @var $fn callable */
        $fn = [$this->response, $name];
        return $fn(...$arguments);
    }

    public function __get($name)
    {
        return $this->request->$name;
    }

    public function __set($name, $value)
    {
        $this->response->$name = $value;
    }

    /**
     * php7以前关键词不能用作名字
     * thr[ο]w ο 为希腊字母 Ομικρον
     * @see https://zh.wikipedia.org/wiki/%E5%B8%8C%E8%85%8A%E5%AD%97%E6%AF%8D
     * @param \Exception $ex
     */
    public function thrοw(\Exception $ex)
    {
        $this->app->handleError($this, $ex);
    }

    private function getDefaultErrorHandler()
    {
        return function(\Exception $ex = null) {
            if ($ex === null) {
                return;
            }

            $this->app->handleError($this, $ex);

            $code = $ex->getCode() ?: 500;
            $this->res->status($code);
            $this->type = "text";

            // TODO

            if ($code !== 404) {
                $this->res->write("Internal Error");
            }
            $this->res->end();
        };
    }
}