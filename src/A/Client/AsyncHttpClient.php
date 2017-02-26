<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/1/31
 * Time: 下午1:50
 */

namespace Minimalism\A\Client;


/**
 * Class AsyncHttpClient
 * @package Minimalism\A
 * @method AsyncHttpClient set(array $setting)
 * @method AsyncHttpClient setMethod(string $method)
 * @method AsyncHttpClient setHeaders(array $headers)
 * @method AsyncHttpClient setCookies(array $cookies)
 * @method AsyncHttpClient setData(string $body)
 * @method AsyncHttpClient setTimeout($ms)
 * @method bool close()
 */
class AsyncHttpClient extends AsyncWithTimeout
{
    public $ip;
    public $port;
    public $cli;
    public $method;
    public $uri;

    public function __construct($ip, $port)
    {
        $this->ip = $ip;
        $this->port = $port;
        $this->cli = new \swoole_http_client($this->ip, $this->port);
    }

    public function __get($name)
    {
        return $this->cli->$name;
    }

    public function __call($name, $arguments)
    {
        $this->cli->$name(...$arguments);
        return $this;
    }

    protected function execute()
    {
        // 这里不能使用lambda, 否则execute回调内无法再次调用execute
        // Fatal error: Cannot destroy active lambda function
        // $this->cli->execute($this->uri, function(\swoole_http_client $cli) {
        //    $this->returnVal($cli);
        // });
        $this->cli->execute($this->uri, [$this, "executeK"]);
    }

    public function executeK(\swoole_http_client $cli)
    {
        $this->returnVal($cli);
    }

    public function setUri($uri)
    {
        $this->uri = $uri;
        return $this;
    }

    public function get($uri, $timeout)
    {
        $this->uri = $uri;
        $this->timeout = $timeout;
        $this->setMethod("GET");
        return $this;
    }

    /**
     * @param $uri
     * @param array|string $data
     * @param $timeout
     * @return $this
     */
    public function post($uri, $data, $timeout)
    {
        $this->uri = $uri;
        $this->timeout = $timeout;
        $this->setMethod("POST");
        $this->setData($data);
        return $this;
    }

    public function postJson($uri, array $data, $timeout)
    {
        $this->uri = $uri;
        $this->timeout = $timeout;
        $this->setMethod("POST");
        $this->setHeaders(["Content-Type" => "application/json;charset=utf-8"]);
        $this->setData(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        return $this;
    }

    public function postForm($uri, array $data, $timeout)
    {
        $this->uri = $uri;
        $this->timeout = $timeout;
        $this->setMethod("POST");
        $this->setHeaders(["Content-Type" => "application/x-www-form-urlencoded;charset=utf-8"]);
        $this->setData(http_build_query($data));
        return $this;
    }

    /*
    public function postData($uri, $data, $timeout)
    {
        $this->uri = $uri;
        $this->timeout = $timeout;
        $this->setMethod("POST");
        $this->setHeaders(["Content-Type" => "multipart/form-data;charset=utf-8"]);
        $this->setData(...);
        return $this;
    }
    */
}