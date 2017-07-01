<?php

/**
 * class swoole_redis
 * 异步 Redis 客户端
 *
 * @since 3.0.4
 *
 * @package swoole_redis
 */

/**
 * class swoole_redis
 *
 * @since 3.0.4
 */
class swoole_redis
{


    /**
     * 执行命令的错误码
     * @var int
     */
    public $errCode = 0;

    /**
     * 连接使用的文件描述符
     * @var int
     */
    public $sock = 0;

    /**
     * redis服务器的端口
     * @var int
     */
    public $port = 0;

    /**
     * 执行命令的错误信息
     * @var string
     */
    public $errMsg = "";

    /**
     * redis服务器的 ip 地址
     * @var string
     */
    public $host = "";

    /**
     * __construct Redis 客户端对象构造函数
     *
     * @since 3.0.4
     *
     */
    public function __construct()
    {
    }

    /**
     * __destruct
     *
     * @since 3.0.4
     * @internal
     */
    public function __destruct()
    {
    }

    /**
     * isConnected 判断连接是否正常
     *
     * @since 3.0.4
     *
     *@return bool true 处于连接状态，false 非连接状态
     */
    public function isConnected()
    {
    }
    
    /**
     * setConnectTimeout 设置连接超时时间,可重复设置，以最后一次设置为准
     *
     * @since 3.0.4
     *
     * @param int $timeout 超时时间，单位ms，设置为0时，不超时
     * @return bool 设置成功返回true，设置失败返回false
     */
    public function setConnectTimeout(int $timeout) { }
    
    /**
     * setQueryTimeout 设置消息发送超时时间,可重复设置，以最后一次设置为准，仅对query生效
     *
     * @since 3.0.4
     *
     * @param int $timeout 超时时间，单位ms，设置为0时，不超时
     * @return bool 设置成功返回true，设置失败返回false
     */
    public function setQueryTimeout(int $timeout) { }
    
    /**
     * on
     *
     * @since 3.0.4
     *
     * @param string   $event_name redis 服务回调事件名称，目前只支持两种事件 onClose、onMessage
     * ```php
     * "close"      // 连接关闭事件
     * "message"    // 订阅消息事件 
     * ```
     * @param callable $callback 事件回调函数，参见onClose/onMessage
     * ```php
     * 连接关闭事件回调函数
     * param swoole_redis $redis redis对象
     * 
     * function onClose(swoole_redis $redis);
     * ```
     * ```php
     * 订阅消息回调
     * param swoole_redis $redis redis对象
     * param array $message      消息内容
     * 
     * 订阅/发布指令没有回调函数，不需要在最后一个参数传入callback
     * 使用订阅/发布消息命名，必须设置onMessage事件回调函数
     * 客户端发出了subscribe命令后，只能执行subscribe， psubscribe，unsubscribe，punsubscribe 这 4 条命令
     *     
     * function onMessage(swoole_redis $redis, array $message);
     *```         
     * 
     * @return bool 成功返回 true，失败返回 false
     */
    public function on(string $event_name, callable $callback)
    {
    }

    /**
     * connect 连接到 Redis 服务端
     *
     * @since 3.0.4
     *
     * @param string   $host redis 服务器 ip 地址
     * @param int      $port redis 服务器监听的端口 port
     * @param callable $callback 连接成功后的回调函数,函数原型参考onConnect
     *                 
     * ```php
     * 连接事件回调
     * @param swoole_redis $redis redis对象
     * @param bool $result 连接结果，true 连接成功，false 连接失败
     * function onConnect(swoole_redis $redis, bool $result);
     * ```
     *
     * @return bool true接口调用成功，false接口调用失败
     */
    public function connect(string $host, int $port, callable $callback)
    {
    }

    /**
     * close 关闭 Redis 连接
     *
     * @since 3.0.4
     *
     * @return bool 成功返回 true，失败返回 false
     */
    public function close()
    {
    }

    /**
     * __call 魔术方法，方法名会映射为Redis指令，参数作为Redis指令的参数。
     *
     * @since 3.0.4
     *
     * @link https://redis.io/commands
     * @param string $command 调用函数名
     * ```php
     *  调用函数名如 get、set、hset，必须为合法的 Redis 指令，subscribe 类型的命令除外
     * ```
     * @param array  $params  函数对应的参数列表 列表最后的元素是结果回调函数，参见，onResult； 其它参数必须为字符串。
     * ```php
     * void onResult(swoole_redis $redis，mixed $result)
     * ```
     * 	
     * @return bool 成功返回 true，失败返回 false
     */
    public function __call(string $command, array $params)
    {
    }

}