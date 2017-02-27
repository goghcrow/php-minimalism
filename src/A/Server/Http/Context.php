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
 * @property array post
 * @property array get
 * @property string files
 * @property array cookie
 * @property array cookies
 * @property array request
 * @property array header
 * @property array headers
 * @property string url
 * @property string originalUrl
 * @property string origin
 * @property string method
 * @property string path
 * @property string query
 * @property string querystring
 * @property string host
 * @property string hostname
 * @property string protocol
 * @property string ip
 *
 * @method string rawcontent()
 *
 * @method bool cookie(string $name, string $value = null, int $expires = null, string $path = null, string $domain = null, bool $secure = null, bool $httponly = null)
 * @method bool rawcookie(string $name, string $value = null, int $expires = null, string $path = null, string $domain = null, bool $secure = null, bool $httponly = null)
 * @method bool status(int $http_code)
 * @method bool gzip(int $compress_level = null)
 * @method bool header(string $key, string $value)
 * @method bool write(string $content)
 * @method bool end(string $content = null)
 * @method bool sendfile(string $filename, int $offset = null)
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

    public $state = [];

    /** @var callable onerror(\Exception $ex) */
    public $onerror;

    /** @var bool */
    public $respond = true;

    /** @var string */
    public $body;

    /** @var int */
    public $status;

    public function __call($name, $arguments)
    {
        if (is_callable($fn = [$this->res, $name])) {
            return $fn(...$arguments);
        } else if (is_callable($fn = [$this->req, $name])) {
            return $fn(...$arguments);
        } else {
            throw new \BadMethodCallException();
        }
    }

    public function __get($name)
    {
        switch ($name) {

            case "post":
                return isset($this->req->post) ? $this->req->post : [];

            case "get":
                return isset($this->req->get) ? $this->req->get : [];

            case "files":
                return isset($this->req->files) ? $this->req->files : [];

            case "cookie":
            case "cookies":
                return isset($this->req->cookie) ? $this->req->cookie : [];

            case "request":
                /** @noinspection PhpUndefinedFieldInspection */
                return isset($this->req->request) ? $this->req->request : [];

            case "header":
            case "headers":
                return isset($this->req->header) ? $this->req->header : [];

            case "method":
                return $this->req->server["request_method"];

            case "url":
            case "originalUrl":
            case "origin":
                return $this->req->server["request_uri"];


            case "path":
                return isset($this->req->server["path_info"]) ? $this->req->server["path_info"] : "";

            case "query":
            case "querystring":
                return isset($this->req->server["query_string"]) ? $this->req->server["query_string"] : "";

            case "host":
            case "hostname":
                return isset($this->req->header["host"]) ? $this->req->header["host"] : "";

            case "protocol":
                return $this->req->server["server_protocol"];

            case "ip":
                return $this->req->server["remote_addr"];


            /*
            case "href":
                break;
                break;
            case "fresh":
                break;
            case "stale":
                break;
            case "socket":
                break;
            case "secure":
                break;
            case "ips":
                break;
            case "subdomains":
                break;
            */

            default:
                return null;
        }
    }

    public function __set($name, $value)
    {
        // TODO
        switch ($name) {
//            case "method":
//                break;
//            case "status":
//                break;
//            case "gzip":
//                break;
//            case "header":
//                break;
//            case "method":
//                break;
//            case "method":
//                break;
//            case "method":
//                break;
//            case "method":
//                break;
        }
    }
}