<?php

/**
 * class swoole_http_client
 * http 客户端
 *
 * @since 3.0.4
 *
 * @package swoole_http_client
 */

/**
 * class swoole_http_client
 *
 * @since 3.0.4
 */
class swoole_http_client
{
    /**
     * 错误码
     * @var int
     */
    public $errCode = 0;

    /**
     * socket 对象句柄
     * @var int
     */
    public $sock = 0;

    /**
     * host地址
     * @var string
     */
    public $host = "";

    /**
     * 端口号
     * @var int
     */
    public $port = 0;

    /**
     * http请求头(包含请求头及其值)，用于parser
     * @var array|null
     */
    public $headers;

    /**
     * 是否设置http客户端属性
     * @var array|null
     */
    public $setting;

    /**
     * setHeaders方法中设置的请求头信息
     * @var array|null
     */
    public $requestHeaders;

    /**
     * http请求body,用于setData、post方法及发送请求
     * @var string|null
     */
    public $requestBody;

    /**
     * http请求方法，用于setMethod
     * @var string|null
     */
    public $requestMethod;

    /**
     * http cookies，用于setMethod及parser
     * @var array|null
     */
    public $cookies;

    /**
     * 解析的body parser_on_message_complete
     * @var string
     */
    public $body = "";

    /**
     *解析完消息的状态码
     * @var int
     */
    public $statusCode = 0;

    /**
     * __construct swoole_http_client 构造函数
     *
     * @since 3.0.4
     *
     * @param string $host 目标主机地址,必须为ip地址，不能为域名
     * @param int    $port [optional] 目标端口，用户可配，默认80
     * @param bool   $ssl [optional] 用户可配，是否开启隧道加密，默认为 false
     *
     */
    public function __construct(string $host, int $port = null, bool $ssl = false)
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

    /**
     * set 配置客户端的一些选项
     *
     * @since 3.0.4
     *
     * @param array $settings 设置http客户端配置选项，
     *```php
     * $settings 参考 swoole_client::set 中的说明,swoole_http_client 额外增加选项:
     *    keep_alive，支持HTTP keep-alive,默认关闭，true 表示启用，false 表示不启动
     *    websocket_mask，默认关闭，启用后 websocket 客户端发送的数据使用掩码进行数据转换
     *```
     * @return bool 成功返回 true，失败返回 false
     */
    public function set(array $settings)
    {
    }
    
    /**
     * setReqTimeout 设置http请求超时时间，对websocket无效
     * 
     * @param int $timeout，单位ms，设置为0时，不超时
     */
    public function setReqTimeout(int $timeout) {}

    /**
     * setMethod 设置 Http 请求方法，仅在当前请求有效，发送请求后会立即清除 method 设置
     *
     * @since 3.0.4
     *
     * @param string $method 设置请求方法，必须是符合 http 标准的方法名称
     *```php
     * 允许方法列表：
     * GET POST PUT DELETE PATCH HEAD OPTIONS
     *```
     *
     * @return bool 成功返回 true，失败返回 false
     */
    public function setMethod($method)
    {
    }

    /**
     * setHeaders 设置 Http 请求头
     *
     * @since 3.0.4
     *
     * @param array $headers 键值对应的数组，映射为 key-value 形式的http标准头格式.
     * ```php
     *  说明：
     *     setHeaders 设置的 Http 头在 swoole_http_client 对象存活期间的每次请求永久有效；
     *     重新调用 setHeaders 会覆盖上一次的设置
     *```
     * @return bool 成功返回 true，失败返回 false
     */
    public function setHeaders(array $headers)
    {
    }

    /**
     * setCookies 设置 cookie
     * 
     * @since 3.0.4
     *
     * @param array $cookies 设置cookies，须为键值对应数组形式
     *
     *```php
     *  说明：
     *     设置 COOKIE 后在客户端对象存活期间会持续保存；
     *     服务端设置的COOKIE会合并到cookies中，可读取对象cookies 属性获得当前 Http 客户端的 COOKIE 信息
     *```
     * @return bool 成功返回 true，失败返回 false
     */
    public function setCookies(array $cookies)
    {
    }

    /**
     * setData  设置 Http 请求的包体 body
     *
     * @since 3.0.4
     *
     * @param string $data 字符串格式
     * ```php
     *  说明：
     *      $data设置但未设置 $method，会自动设置为 POST方法；
     *      $data未且未设置 $method，会自动设置为 GET方法.
     *```
     * @return bool 成功返回 true，失败返回 false
     */
    public function setData(string $data)
    {
    }

    /**
     * execute 更底层的 Http 请求方法，需要代码中调用 setMethod 和 setData 等接口设置请求的方法和数据。
     *
     * @since 3.0.4
     *
     * @param string   $path     uri
     * @param callable $callback 回调函数，原型参考onRequest
     * ```php
     * onRequest 函数原型
     * param swoole_http_client $client http_client对象，
     * function onRequest(swoole_http_client $client) {}
     * ```
     *
     * @return bool 成功返回 true，失败返回 false
     */
    public function execute(string $path, callable $callback)
    {
    }

    /**
     * push  向对端推送数据，长度最大不得超过2M，只用于 websocket
     *
     * @since 3.0.4
     *
     * @param string $data 要发送的数据内容
     * @param int    $opcode [optional]  指定发送数据内容的格式，默认为文本 WEBSOCKET_OPCODE_TEXT_FRAME，值为 1。
     *                                   发送二进制内容$opcode参数需要设置为WEBSOCKET_OPCODE_BINARY_FRAME，值为 2。
     * @param bool   $finish [optional]  一个message分为多个frame传输时，finish 为 false 表示message未完，为 true 代表最后一个frame。
     *
     * @return bool 执行成功返回 true，失败返回 false
     */
    public function push(string $data, int $opcode = 1, bool $finish = true)
    {
    }

    /**
     * get  发送 get 请求
     *
     * @since 3.0.4
     *
     * @param string   $path 设置uri路径，如/index.html，注意这里不能传入http://domain
     * @param callable $callback 调用成功或失败后回调此函数，参考onRequest
     *
     * @return bool 执行成功返回 true，失败返回 false
     */
    public function get(string $path, callable $callback)
    {
    }

    /**
     * post  发送 post 请求
     *
     * @since 3.0.4
     *
     * @param string       $path 设置uri路径，注意这里不能传入http://domain
     * @param string|array $data 发送起请求的 body 数据，如果 $data 为数组底层自动会打包为x-www-form-urlencoded格式的POST内容，
     *                           并设置Content-Type为application/x-www-form-urlencoded
     * @param callable     $callback 调用成功或失败后回调此函数，参考onRequest
     *
     * @return bool 执行成功返回 true，失败返回 false
     */
    public function post(string $path, mixed $data, callable $callback)
    {
    }

    /**
     * upgrade  发起 WebSocket 握手请求，并将连接升级为 WebSocket
     * 使用该方法必须使用on设置onMessage 回调函数
     *
     * @since 3.0.4
     *
     * @param string   $path URL路径
     * @param callable $callback 握手成功或失败后回调此函数
     *
     * @return bool 成功返回 true，失败返回 false
     */
    public function upgrade(string $path, callable $callback)
    {
    }

    /**
     * isConnected  判断当前连接是否处于 active 状态
     *
     * @since 3.0.4
     *
     * @return bool active 状态返回 true，否则返回 false
     */
    public function isConnected()
    {
    }

    /**
     * close 关闭连接，http_client 调用 close 关闭连接后，如果再次请求 get、post等方法，底层会重新连接服务端
     *
     * @since 3.0.4
     *
     * @return bool 成功返回 true，失败返回 false
     */
    public function close()
    {
    }

    /**
     * on 注册 http_client 事件回调函数
     *
     * @since 3.0.4
     *
     * @param string   $event_name 回调事件名（大小写不敏感）
     * ```php
     * "connect"    // 连接成功事件
     * "error"      // 连接错误事件
     * "message"    // websocket 消息事件
     * "close"      // 连接关闭事件
     * ```
     * @param callable $callback 回调函数，必须是可调用的，函数原型参考onConnect，onError，onClose，onMessage
     * ```php
     * param swoole_http_client $client   http_client对象
     * function onConnect($swoole_http_client $client)
     * ```
     * ```php
     * param swoole_http_client $client   http_client对象
     * function onError($swoole_http_client $client);
     * ```
     *```php
     *param swoole_http_client $client   http_client对象
     *function onClose($swoole_http_client $client)
     *```
     *```php
     *param swoole_http_client $client   http_client对象
     *param swoole_websocket_frame       websocket 对象
     *function onMessage(swoole_http_client $client, swoole_websocket_frame $frame);
     *```
     *
     * @return bool 成功返回 true，失败返回 false
     */
    public function on(string $event_name, callable $callback)
    {
    }

}
