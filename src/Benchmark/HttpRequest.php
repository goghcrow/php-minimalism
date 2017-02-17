<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/17
 * Time: 上午1:32
 */

namespace Minimalism\Benchmark;


class HttpRequest
{
    public $uri = "/";
    public $method = "GET";
    public $headers = ["Connection" => "Keep-Alive"];
    public $cookies = [];
    public $body = "";
}