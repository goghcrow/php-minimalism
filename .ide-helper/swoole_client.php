<?php
/**
 * class swoole_client
 *
 * @since 3.0.4
 *
 * @package swoole_client
 */

/**
 * class swoole_client
 *
 * @since 3.0.4
 */
class swoole_client
{

    /**
     * 读取带外数据
     */
    const MSG_OOB = 1;

    /**
     * recv 方法的第二个参数，窥视socket缓存区中的数据。
     * 设置MSG_PEEK参数后，recv读取数据不会修改指针，因此下一次调用recv仍然会从上一次的位置起返回数据。
     */
    const MSG_PEEK = 2;

    /**
     * recv 方法的第二个参数，非阻塞接收数据，无论是否有数据都会立即返回
     * @const
     */
    const MSG_DONTWAIT = 128;

    /**
     * recv 方法的第二个参数，阻塞等待直到收到指定长度的数据后返回。
     * @const
     */
    const MSG_WAITALL = 64;

    /**
     * 错误码，当 connect/send/recv/close 失败时，会自动设置 $swoole_client->errCode 的值。
     * errCode 的值等于 Linux errno，可使用 socket_strerror 将错误码转为错误信息。
     * @var int 错误码
     */
    public $errCode = 0;

    /**
     * 表示此 socket 连接的文件描述符
     * @var int socket 连接的描述符
     */
    public $sock = 0;

    /**
     * @var
     * @internal
     */
    public $reuse;

    /**
     * @internal
     * @var
     */
    public $type;

    /**
     * @internal
     * @var
     */
    public $id;

    /**
     * 客户端设置的选项，具体见 set() 接口
     * @var array
     */
    public $setting;

    /**
     * __construct swoole_client 构造函数
     *
     * @since 3.0.4
     *
     * @param int    $sock_type socket 类型，支持 TCP/UDP、TCP6/UDP6、UinxSock Stream/Dgram 6种
     * ```php
     *  SWOOLE_SOCK_TCP/SWOOLE_TCP
     *  SWOOLE_SOCK_UDP/SWOOLE_UDP
     *  SWOOLE_SOCK_TCP6/SWOOLE_TCP6
     *  SWOOLE_SOCK_UDP6/SWOOLE_UDP6
     *  SWOOLE_SOCK_UNIX_STREAM/SWOOLE_SOCK_UNIX_STREAM
     *  SWOOLE_SOCK_UNIX_DGRAM/SWOOLE_UNIX_DGRAM
     *  使用 $sock_type|SWOOLE_SSL可以启用SSL加密，SSL 加密算法相关常量参考全局常量部分
     * ```
     * @param int    $is_sync 表示同步阻塞还是异步非阻塞，默认为同步阻塞
     * @param string $key $key用于长连接的Key，默认使用IP:PORT作为key。相同key的连接会被复用
     *
     */
    public function __construct(int $sock_type, int $is_sync = SWOOLE_SOCK_SYNC, string $key = null)
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

    /**
     * set 设置客户端参数，必须在connect前执行。
     *
     * @since 3.0.4
     *
     * @param array $settings 数组，设置
     * ```php
     * client 端可以使用 set 方法设置一些选项，启用某些特性：
     * 结束符检测：
     *  open_eof_check
     *  package_eof
     *  package_max_length
     *
     * 长度检测：
     *  open_length_check
     *  package_length_type
     *  package_length_offset
     *  package_body_offset
     *  package_max_length
     *
     * Socket 缓冲区尺寸：
     *  socket_buffer_size
     *
     * 关闭Nagle合并算法：
     *  open_tcp_nodelay
     *
     * SSL/TLS证书：
     *  ssl_cert_file
     *  ssl_key_file
     *
     * 绑定IP和端口：
     *  bind_address
     *  bind_port
     *
     * Socks5代理设置：
     *  socks5_host
     *  socks5_port
     *  socks5_username
     *  socks5_password
     * ```
     * @return bool 成功返回 true
     */
    public function set(array $settings)
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
    public function setConnectTimeout(int $timeout)
    {
    }
    
    /**
     * setSendTimeout 设置消息发送超时时间,可重复设置，以最后一次设置为准，仅对sendWithCallback接口有效
     *
     * @since 3.0.4
     *
     * @param int $timeout 超时时间，单位ms，设置为0时，不超时
     * @return bool 设置成功返回true，设置失败返回false
     */
    public function setSendTimeout(int $timeout)
    {
    }
    
    /**
     * connect 连接到远程服务端，与setConnectTimeout结合使用，可支持连接超时
     *
     * @since 3.0.4
     *
     * @param string $host 远程服务的地址
     * ```php
     * 说明：
     *     使用异步swoole_client时，host不能为域名，必须为ip
     * ```
     * @param int    $port 远程服务的端口
     * @param int    $flag [optional] 可选字段，默认为 0
     *                             UDP client：$flag=1 表示启用 udp_connect，将绑定 $host 和 $port，此 UDP 连接将丢弃非指定 $host:$port 的数据包。
     *                             TCP client：$flag=1 表示设置为非阻塞 socket，connect 会立即返回。
     *
     * @return bool 调用成功返回true，调用失败false
     */
    public function connect(string $host, int $port, int $flag = 0)
    {
    }

    /**
     * recv 从服务端接收数据
     *
     * @since 3.0.4
     *
     * @param int $size 接收数据的缓冲区最大长度
     * @param int $flags 可以设置一些特殊的 SOCKET 接收设置
     *                  $client->recv(8192, swoole_client::MSG_PEEK | swoole_client::MSG_WAITALL);
     *
     * @return string|false 成功收到数据返回接收到的字符串，连接关闭返回空字符串，失败返回 false，并设置 $swoole_client->errCode 属性
     */
    public function recv(int $size = 65535, int $flags = 0)
    {
    }

    /**
     * send 发送数据到服务端，必须在建立连接之后调用
     *
     * @since 3.0.4
     *
     * @param string $data 要发送到服务端的数据
     * @param int    $flag 具体意义可以参考 php 官方 send 接口中 flag 的定义，只对同步方式下直作用。如下：
     * @link  http://php.net/manual/en/function.socket-send.php
     *
     * @return int|false 调用成功返回 errno 值，失败返回 false
     *
     */
    public function send(string $data, int $flag = 0)
    {
    }

    /**
     * sendWithCallback 发送数据到服务端，必须在建立连接之后调用，支持超时发送，与setSendTimeout结合使用
     *
     * @since 3.0.4
     *
     * @param string    $data 要发送到服务端的数据
     * @param callable  $callback 回调函数
     * 					函数原型为：function onReceive(swoole_client $client, string $data)
     *
     * @return int|false 调用成功返回 errno 值，失败返回 false
     *
     */
    public function sendWithCallback(string $data,callable $callback)
    {
    }
    
    /**
     * sendfile 发送文件到服务端，本函数是基于 sendfile 操作系统调用的。
     *
     * @since 3.0.4
     *
     * @param string $filename 要发送的文件路径，如果文件不存在函数返回 false
     *
     * @return bool 发送成功返回 true，失败返回 false
     */
    public function sendfile(string $filename)
    {
    }

    /**
     * sendto 向任意 IP:PORT 的主机发送 UDP 数据包，仅支持 SWOOLE_SOCK_UDP/SWOOLE_SOCK_UDP6 类型的 swoole_client 对象。
     *
     * @since 3.0.4
     *
     * @param string $ip 目标服务 ip 地址，支持 ipv4/ipv6
     * @param int    $port 目标服务端口
     * @param string $data 要发送的数据内容，建议不超过 64k
     *
     * @return bool 成功返回 true，失败返回 false
     */
    public function sendto(string $ip, int $port, string $data)
    {
    }

    /**
     * sleep 此方法会从事件循环中移除当前 socket 的可读监听，停止接收数据。
     * 此方法仅停止从 socket 中接收数据，但不会移除可写事件，所以不会影响发送队列
     * sleep 操作与 wakeup 作用相反，使用 wakeup 方法可以重新监听可读事件
     *
     * @since 3.0.4
     *
     * @return bool 成功返回 true，失败返回 false
     */
    public function sleep()
    {
    }

    /**
     * wakeup 调用此方法会重新监听可读事件，将 socket 连接从睡眠中唤醒。
     * 如果socket并未进入sleep模式，wakeup操作没有任何作用
     *
     * @since 3.0.4
     *
     * @return bool 成功返回 true，失败返回 false
     */
    public function wakeup()
    {
    }

    /**
     * isConnected 返回当前客户端的连接状态
     *
     * @since 3.0.4
     *
     * @return bool true 表示当前客户端已经连接到服务端，false 表示当前客户端未连接到服务端
     */
    public function isConnected()
    {
    }

    /**
     * getsockname 用于获取客户端socket的本地host:port，必须在连接之后才可以使用。
     *
     * @since 3.0.4
     *
     * @return array|false 成功返回一个数组，形如 Array([port] => 59946，[host] => 127.0.0.1)，失败返回false
     */
    public function getsockname()
    {
    }

    /**
     * getpeername 获取对端 socket 的IP地址和端口，仅支持 SWOOLE_SOCK_UDP/SWOOLE_SOCK_UDP6 类型的 swoole_client 对象。
     * UDP协议通信客户端向一台服务器发送数据包后，可能并非由此服务器向客户端发送响应。可以使用此方法获取实际响应的服务器IP:PORT。
     *
     * @since 3.0.4
     * @return array|false 成功返回一个数组，形如 Array([port] => 0，[host] => 0.0.0.0)，失败返回 false
     */
    public function getpeername()
    {
    }

    /**
     * close 关闭连接
     *
     * @since 3.0.4
     *
     * @param bool $is_force true 表示强制关闭连接,该标记已经废弃
     *
     * @return bool 操作成功返回 true，失败返回 false
     */
    public function close(bool $is_force = false)
    {
    }

    /**
     * 注册异步事件回调函数，调用 on 方法会使当前的 socket 变成非阻塞的
     *
     * @since 3.0.4
     *
     *
     * @param string   $event_name 事件名，不区分大小写
     * ```php
     * "connect" 连接成功事件,对应事件的回调函数原型参考onConnect
     * 	  function onConnect(swoole_client $client)
     * "receive" 收到数据事件，对应事件的回调函数原型参考onReceive
     *    function onReceive(swoole_client $client, string $data)
     * "close"   关闭事件，对应事件的回调函数原型参考onClose
     *    function onClose(swoole_client $client)
     * "error"   网络错误事件，对应事件的回调函数原型参考onError
     *    function onError(swoole_client $client)
     * ```
     * @param callable $callback 发生 $event_name 事件时的回调函数
     *
     * @return
     */
    public function on(string $event_name, callable $callback)
    {
    }

    /**
     * getSocket 返回底层的 socket 句柄，返回的对象为 socket 资源句柄
     *
     * @since 3.0.4
     *
     * @return resource|false 成功返回 socket 资源句柄，失败返回 false
     */
    public function getSocket()
    {
    }

}
