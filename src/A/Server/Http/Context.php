<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/26
 * Time: 上午1:34
 */

namespace Minimalism\A\Server\Http;
use Minimalism\A\Server\Http\Exception\HttpException;


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
 * getter property array request
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
     * allow overwrite
     * @var callable onError(\Exception $ex)
     */
    public $onError;

    /**
     * allow user data
     * @var array
     */
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
        $this->onError = $this->errorHandler();
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
     * 抛出带状态码Http异常
     * php7以前关键词不能用作名字
     * thr[ο]w ο 为希腊字母 Ομικρον
     * @see https://zh.wikipedia.org/wiki/%E5%B8%8C%E8%85%8A%E5%AD%97%E6%AF%8D
     * @param int $status
     * @param string|\Exception $message
     * @throws HttpException
     */
    public function thrοw($status, $message)
    {
        // TODO utils createHttpException
        if ($message instanceof \Exception) {
            $ex = $message;
            throw new HttpException($status, $ex->getMessage(), $ex->getCode(), $ex->getPrevious());
        } else {
            throw new HttpException($status, $message);
        }
    }

    private function errorHandler()
    {
        return function(\Exception $ex = null) {
            if ($ex === null) {
                return;
            }

            // delegate
            $this->app->emit("error", $this, $ex);

            // TODO accepts
            $this->type = "text"; // force text/plain

            // 非 Http异常, 统一500 status, 对外显示异常code
            // Http 异常, 自定义status, 自定义是否暴露Msg
            $msg = $ex->getCode();

            if ($ex instanceof HttpException) {
                $status = $ex->status ?: 500;
                $this->res->status($status);
                if ($ex->expose) {
                    $msg = $ex->getMessage();
                }
            } else {
                $this->res->status(500);
            }

            $this->res->write($msg);
            $this->res->end();
        };
    }

    public function __toString()
    {
        return json_encode([
            "request" => [
                "url" => $this->url,
                "method" => $this->method,
                "headers" => $this->header,
            ],
            "response" => [
                "status" => $this->status,
                // "message" => status message,
                "headers" => $this->res->header,
            ],
        ], JSON_PRETTY_PRINT);
    }
}