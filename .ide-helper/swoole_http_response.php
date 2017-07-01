<?php

/**
 * class swoole_http_response
 *
 * @since 3.0.4
 *
 * @package swoole_http_response
 */

/**
 * class swoole_http_response
 *
 * @since 3.0.4
 */
class swoole_http_response
{
    /**
     * socket fd
     * @var int
     * @internal
     */
    public $fd = 0;

    /**
     * http response的缓存
     * @var array
     */
    public $cookie;

    /**
     * http response头
     * @var array|string
     */
    public $header;

    /**
     * cookie 设置 HTTP 响应的 cookie 信息。此方法参数与 PHP 的 setcookie 完全一致。
     *
     * @since 3.0.4
     *
     * @param string $name key 的名称，小写
     * @param string $value [optional]  $name 对应的 value 值
     * @param int    $expires [optional]   过期时间
     * @param string $path [optional]
     * @param string $domain [optional]
     * @param bool   $secure [optional]
     * @param bool   $httponly [optional]
     *
     * @return void
     */
    public function cookie(string $name, string $value = '', int $expires = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httponly = false)
    {
    }

    /**
     * rawcookie
     *
     * @since 3.0.4
     *
     * @param $name
     * @param $value [optional]
     * @param $expires [optional]
     * @param $path [optional]
     * @param $domain [optional]
     * @param $secure [optional]
     * @param $httponly [optional]
     *
     * @todo 。。不删除
     * @return
     */
    public function rawcookie($name, $value = null, $expires = null, $path = null, $domain = null, $secure = null, $httponly = null)
    {
    }

    /**
     * status 发送 Http 状态码
     * @since 3.0.4
     *
     * @param int $http_code 必须为合法的 HttpCode，如200， 502， 301, 404等，否则会报错
     *                       必须在 $response->end 之前执行 status
     *
     * @return void
     */
    public function status(int $http_code)
    {
    }

    /**
     * gzip 启用Http GZIP压缩。压缩可以减小HTML内容的尺寸，有效节省网络带宽，提高响应时间。
     * 必须在write/end发送内容之前执行gzip，否则会抛出错误。
     *
     * @since 3.0.4
     *
     * @param int $compress_level [optional] 压缩等级，范围是1-9，等级越高压缩后的尺寸越小，但CPU消耗更多。默认为1
     *
     * @return
     */
    public function gzip(int $compress_level = 1)
    {
    }

    /**
     * header 设置 HTTP 响应的 Header 信息。
     * $key必须完全符合Http的约定，每个单词首字母大写，不得包含中文，下划线或者其他特殊字符
     * $value必须填写，示例：
     * $responser->header('Content-Type', 'image/jpeg');
     *
     * @since 3.0.4
     *
     * @param string $key Http头的Key
     * @param string $value Http头的Value
     * @param bool   $ucwords [optional]
     *
     * @return
     */
    public function header(string $key, string $value, bool $ucwords = true)
    {
    }

    /**
     * write 启用 Http Chunk 分段向浏览器发送相应内容。关于 Http Chunk 可以参考 Http 协议标准文档。
     * $content 要发送的数据内容，最大长度不得超过2M
     * 使用 write 分段发送数据后，end 方法将不接受任何参数
     * 调用 end 方法后会发送一个长度为 0 的 Chunk 表示数据传输完毕
     * @since 3.0.4
     *
     * @param string $content
     *
     * @return bool 成功返回 true，失败返回 false
     */
    public function write(string $content)
    {
    }

    /**
     * end 发送Http响应体，并结束请求处理。
     *
     * @since 3.0.4
     *
     * @param string $content [optional] 向客户端浏览器发送的 html 内容
     *
     * @return bool 成功返回 true，失败返回 false
     */
    public function end(string $content = null)
    {
    }

    /**
     * sendfile 发送文件到浏览器。
     *
     * @since 3.0.4
     *
     * @param string $filename 要发送的文件名称
     *
     * @return bool 成功返回 true，失败返回 false
     */
    public function sendfile(string $filename)
    {
    }

    /**
     * __destruct 析构函数
     *
     * @since 3.0.4
     * @internal
     *
     */
    public function __destruct()
    {
    }

}