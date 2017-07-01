<?php

/**
 * class swoole_http_request
 *
 * @since 3.0.4
 *
 * @package swoole_http_request
 */

/**
 * class swoole_http_request
 *
 * @since 3.0.4
 */
class swoole_http_request
{

    /**
     * http 发送请求
     * @var string
     * @internal
     */
    public $request;

    /**
     * Http 请求的 GET 参数，相当于 PHP 中的 $_GET，格式为数组。
     * @var array
     */
    public $get;

    /**
     * Http 请求携带的 cookie 信息，与 PHP 的 $_COOKIE 相同，格式为数组。
     * @var array
     */
    public $cookie;

    /**
     * 文件上传信息，类型为以 form 名称为 key 的二维数组。与 PHP 的 $_FILES 相同。
     * @var array
     * ```php
     * Array
     * (
     * [name] => test.jpg
     * [type] => image/jpeg
     * [tmp_name] => /tmp/swoole.upfile.n3FmFr
     * [error] => 0
     * [size] => 15476
     * )
     * name 浏览器上传时传入的文件名称
     * type MIME类型
     * tmp_name 上传的临时文件，文件名以/tmp/swoole.upfile开头
     * size 文件尺寸
     * ```
     */
    public $files;

    /**
     * HTTP POST参数，格式为数组。
     * POST 与 Header 加起来的尺寸不得超过 package_max_length 的设置，否则会认为是恶意请求
     * @var array post 参数的个数最大不超过128个
     */
    public $post;

    /**
     * http发送请求的文件描述符
     * @var int
     */
    public $fd = 0;

    /**
     * http 发送请求的请求头
     * @var array 类型为数组，所有 key 均为小写
     */
    public $header;

    /**
     * Http 请求相关的服务器信息，相当于PHP的$_SERVER数组。包含了Http请求的方法，URL 路径，客户端 IP 等信息。
     * @var array
     */
    public $server;

    /**
     * rawcontent 获取原始的 POST 包体，用于非 application/x-www-form-urlencoded 格式的 Http POST 请求。
     * 返回原始POST数据，此函数等同于 PHP 的 fopen('php://input')。标准 POST 格式，无法调用此函数
     * @since 3.0.4
     *
     * @return bool 成功返回 true，失败返回 false
     */
    public function rawcontent()
    {
    }

    /**
     * __destruct swoole_http_client 析构函数
     *
     * @since 3.0.4
     * @internal
     *
     */
    public function __destruct()
    {
    }

}