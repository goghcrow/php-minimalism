<?php
/**
 * class swoole_server_port
 *
 * @since 3.0.4
 *
 * @package swoole_server_port
 */

/**
 * class swoole_server_port
 *
 * @since 3.0.4
 */
class swoole_server_port
{

    /**
     * 数组，通过 set 设置的一些特定参数的 key-value 数组
     * @var array
     */
    public $setting;

    /**
     * @var callable
     * @internal
     */
    public $onConnect;

    /**
     * @var callable
     * @internal
     */
    public $onReceive;

    /**
     * @var callable
     * @internal
     */
    public $onClose;

    /**
     * @var callable
     * @internal
     */
    public $onPacket;

    /**
     * Server可以监听多个端口
     *
     * ```php
     *  每个端口都可以设置不同的协议处理方式(set)和回调函数(on)。SSL/TLS传输加密也可以只对特定的端口启用。
     *  未设置协议处理选项的监听端口，默认使用无协议模式；
     *  未设置回调函数的监听端口，使用$server对象的回调函数；
     *  监听端口返回的对象类型为swoole_server_port；
     *  不同监听端口的回调函数，仍然是相同的Worker进程空间内执行；
     *
     * 监听新端口：
     *  $port1 = $server->listen("127.0.0.1", 9501, SWOOLE_SOCK_TCP);
     *  $port2 = $server->listen("127.0.0.1", 9502, SWOOLE_SOCK_UDP);
     *  $port3 = $server->listen("127.0.0.1", 9503, SWOOLE_SOCK_TCP | SWOOLE_SSL);
     *
     *  设置网络协议：
     *  $port1->set([
     *      'open_length_check' => true,
     *      'package_length_type' => 'N',
     *      'package_length_offset' => 0,
     *      'package_max_length' => 800000,
     *  ]);
     *  $port3->set([
     *      'open_eof_split' => true,
     *      'package_eof' => "\r\n",
     *      'ssl_cert_file' => 'ssl.cert',
     *      'ssl_key_file' => 'ssl.key',
     *  ]);
     *
     * 设置回调函数示例：
     *  $port1->on('connect', function ($serv, $fd){
     *      echo "Client:Connect.\n";
     *  });
     *
     *  $port1->on('receive', function ($serv, $fd, $from_id, $data) {
     *      $serv->send($fd, 'Swoole: '.$data);
     *      $serv->close($fd);
     *  });
     * ```
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
     *
     * @internal
     *
     * @return
     */
    public function __destruct()
    {
    }

    /**
     * set 监听端口调用set方法只能设置一些特定的参数，无法修改全局的Server设置。
     *
     * @since 3.0.4
     *
     * @param array $set 数组
     * ```php
     *  监听端口未设置任何参数，将会继承主服务器的相关配置
     *  Http/WebSocket服务器，如果未设置协议参数，监听的端口仍然会设置为Http或WebSocket协议，并且不会执行为端口设置的onReceive回调
     *  可用的参数列表
     *      socket参数，如backlog、TCP_KEEPALIVE、open_tcp_nodelay、tcp_defer_accept等
     *      协议相关，如open_length_check、open_eof_check、package_length_type等
     *      SSL证书相关，如ssl_cert_file、ssl_key_file等
     *  不可用的参数列表
     *      worker_num、task_worker_num、reactor_num
     *      dispatch_mode、task_ipc_num
     *      heartbeart_check
     *      log_file
     *      user/group/chroot
     *      open_cpu_affinity
     *      max_request/task_max_request
     * ```
     *
     * @return
     */
    public function set(array $set)
    {
    }

    /**
     * on 监听端口使用 on 方法可以设置部分回调函数。
     *
     * @since 3.0.4
     *
     * @param $name
     * @param $callback
     *```php
     * 可选回调
     * 监听端口使用on方法可以设置部分回调函数。
     *  TCP服务器
     *      onConnect
     *      onClose
     *      onReceive
     *
     *  UDP服务器
     *      onPacket
     *      onReceive
     *
     * 不可用回调
     * 以下事件回调函数是Server级别的，只能在swoole_server对象上设置。
     *      onStart
     *      onShutdown
     *      onWorkerStart
     *      onWorkerStop
     *      onManagerStart
     *      onManagerStop
     *      onTask
     *      onFinish
     *      onPipeMessage
     *      onWorkerError
     * ```
     *
     * @return
     */
    public function on(string $name, callable $callback)
    {
    }

}
