<?php

/**
 * global function
 *
 * @since 3.0.4
 *
 * @package global_function
 */

/**
 * 获取当前 swoole 扩展的版本号，如 3.0.4
 * @since 3.0.4
 * @api
 * @global function
 * @return string 返回当前 swoole 扩展的版本号
 */
function swoole_version()
{
}

/**
 * swoole_cpu_num 获取当前服务器 cpu 的核心数
 * @since 3.0.4
 *
 * @api
 * @global function
 * @return int 返回当前服务器 cpu 的核心数
 */
function swoole_cpu_num()
{
}

/**
 * nova协议解包
 *
 * @since 3.0.4
 *
 * @param string  $buf 二进制字符串
 * @param &string $service_name 服务名
 * @param &string $method_name 方法名
 * @param &string $ip
 * @param &int    $port
 * @param &int    $seq_no
 * @param &string $attach 附加字段 通常为json编码字符串
 * @param &string $data nova body
 *
 * @return bool 成功返回 true，失败返回 false
 */
function nova_decode(string $buf, string &$service_name, string &$method_name, string &$ip, int &$port, int &$seq_no, string &$attach, string &$data)
{
}

/**
 * nova协议解包
 *
 * @since 3.0.4
 *
 * @param string $service_name
 * @param string $method_name
 * @param string $ip
 * @param int    $port
 * @param int    $seq_no
 * @param string $attach 附加字段 通常为json编码字符串
 * @param string $data 协议body
 * @param &string $buf 打包结果
 *
 * @return bool 成功返回 true，失败返回 false
 */
function nova_encode(string $service_name, string $method_name, string $ip, int $port, int $seq_no, string $attach, string $data, string &$buf)
{
}

/**
 * 判断一个二进制包是否是 nova 包
 * @since 3.0.4
 *
 * @param string $data
 *
 * @return bool 成功返回 true，失败返回 false
 */
function is_nova_packet(string $data)
{
}

/**
 * 获取一个自增的序列号 id
 * @since 3.0.4
 *
 * @return int
 */
function nova_get_sequence()
{
}

/**
 * 获取事件循环的时间戳
 * @since 3.0.4
 *
 * @return int unix 时间戳
 */
function nova_get_time()
{
}

/**
 * 获取本机 ip 地址，非 127.0.0.1 的第一个 ip 地址
 * @since 3.0.4
 *
 * @return string 成功返回第一个非local ip地址，失败返回空字符串
 */
function nova_get_ip()
{
}


/**
 * Swoole扩展还提供了直接操作底层epoll/kqueue事件循环的接口。可将其他扩展创建的socket，PHP代码中stream/socket扩展创建的socket等加入到Swoole的EventLoop中。
 * 用于将一个socket加入到swoole的reactor事件监听中，此函数可以用在Server或Client模式下
 * @since 3.0.4
 * @api
 * @global              function
 *
 * @param int      $fd 就是文件描述符,包括swoole_client的socket,以及第三方扩展的socket（比如mysql)
 *                     stream资源，就是stream_socket_client/fsockopen 创建的资源
 *                     sockets资源，就是sockets扩展中 socket_create创建的资源，需要在编译时加入 ./configure --enable-sockets
 * @param callable $read_callback 可读事件回调函数
 * @param callable $write_callback [optional] 可写事件回调函数
 * @param int      $event_flag [optional] 事件类型掩码，可选择关闭/开启可读可写事件，
 *                      如 SWOOLE_EVENT_READ，SWOOLE_EVENT_WRITE，或者SWOOLE_EVENT_READ | SWOOLE_EVENT_WRITE
 *
 * @return bool 成功返回 true，失败返回 false
 */
function swoole_event_add(int $fd, callable $read_callback, callable $write_callback = null, int $event_flag = null)
{
}

/**
 * 修改事件监听的回调函数和掩码，参数同 swoole_event_add
 * @since 3.0.4
 *
 * @param int      $fd                文件描述符，同 swoole_event_add
 * @param callable $read [optional]   修改可读事件回调为指定函数
 * @param callable $write [optional]  修改可写事件回调为指定函数
 * @param int      $events [optional] 可关闭/开启，可写（SWOOLE_EVENT_READ）和可读（SWOOLE_EVENT_WRITE）事件的监听
 *
 * @return bool 成功返回true，失败返回 false
 */
function swoole_event_set(int $fd, callable $read = null, callable $write = null, int $events = null)
{
}

/**
 * 用于从reactor中移除监听的socket。swoole_event_del应当与swoole_event_add成对使用。
 * @since 3.0.4
 *
 * @param $fd
 *
 * @return void
 */
function swoole_event_del(int $fd)
{
}

/**
 * 退出事件轮询，此函数仅在Client程序中有效。
 * @since 3.0.4
 *
 * @return void
 */
function swoole_event_exit()
{
}

/**
 * PHP5.4之前的版本没有在ZendAPI中加入注册shutdown函数。所以swoole无法在脚本结尾处自动进行事件轮询。
 * 所以低于5.4的版本，需要在你的PHP脚本结尾处加swoole_event_wait函数。使脚本开始进行事件轮询。
 * @since 3.0.4
 *
 * @return void
 */
function swoole_event_wait()
{
}

/**
 * 用于PHP自带stream/sockets扩展创建的socket，使用fwrite/socket_send等函数向对端发送数据。
 * @since 3.0.4
 *
 * @param int   $fd 文件句柄，同 swoole_event_add
 * @param mixed $data 要发送的数据
 *
 * @return bool 成功返回 true，失败返回 false
 */
function swoole_event_write(int $fd, $data)
{
}

/**
 * 在当前EventLoop的事件循环结束、下一次事件循环启动时响应，$callback 函数不接受任何参数
 * @since 3.0.4
 *
 * @param callable $callback 时间到期后执行的函数，必须是可调用的，不接受任何参数
 *
 * @return void
 */
function swoole_event_defer(callable $callback)
{
}

/**
 * 在指定的时间 $ms 后执行函数 $callback，执行完后定时器就会销毁，非阻塞。
 * @since 3.0.4
 *
 * @param int      $ms 指定时间，单位毫秒
 * @param callable $callback 回调函数
 * @param mixed    $param [optional] 用户参数，该参数被传递给 $callback 中，如果有多个参数可以使用数组形式
 *
 * @return int|bool 成功返回定时器 id，失败返回false
 */
function swoole_timer_after(int $ms, callable $callback, mixed $param = null)
{
}

/**
 * 设置一个间隔时钟定时器，与after定时器不同的是tick定时器会持续触发，直到调用swoole_timer_clear清除。
 * @since 3.0.4
 *
 * @param int      $ms 指定时间，单位毫秒
 * @param callable $callback 时间到期后执行的函数，必须是可调用的
 * @param mixed    $param [optional] 用户参数，该参数被传递给 $callback 中，如果有多个参数可以使用数组形式
 *
 * @return int|bool 成功返回定时器 time_id，失败返回 false
 */
function swoole_timer_tick(int $ms, callable $callback, mixed $param = null)
{
}

/**
 * 判断 $time_id 标识的定时器是否存在
 * @since 3.0.4
 *
 * @param int $timer_id 定时器 id
 *
 * @return bool 定时器 $timer_id 存在返回 true，不存在返回 false
 */
function swoole_timer_exists(int $timer_id)
{
}

/**
 * 清除本当前进程中 $timer_id 标识的定时器
 * @since 3.0.4
 *
 * @param int $timer_id 定时器标识
 *
 * @return bool 成功返回true，失败返回 false
 */
function swoole_timer_clear($timer_id)
{
}

/**
 * 设置异步IO操作配置
 *
 * @since 2.0.0
 * @link https://wiki.swoole.com/wiki/page/182.html
 *
 * @param array $settings
 * ```php
 *  thread_num 设置异步文件IO线程的数量
 *  aio_mode 设置异步文件IO的操作模式，目前支持SWOOLE_AIO_BASE（使用类似于Node.js的线程池同步阻塞模拟异步）、SWOOLE_AIO_LINUX（Linux Native AIO） 2种模式
 *  enable_signalfd 开启和关闭signalfd特性的使用
 *  socket_buffer_size 设置SOCKET内存缓存区尺寸
 *  socket_dontwait 在内存缓存区已满的情况下禁止底层阻塞等待
 *  补充选项：“aio_max_buffer” ＝> 1*1024*1024，设置aio最大buf
 * ```
 *
 * @return void
 */
function swoole_async_set(array $settings)
{
}

/**
 * 异步读取文件数据
 *
 * 若读取数据较大，则回多次回调用户；最后一次回调数据长度为0，表示读取结束
 *
 * @since 2.0.0
 *
 * @param string $filename 文件名
 * @param string $callback 回调函数
 * @param int    $chunk_size [optional] 读取数据的长度，默认-1:读取整个文件.
 * @param int    $offset [optional]     文件起始偏移量，从文件偏移开始读取，默认为0
 *
 * @return bool 成功返回 true，失败返回 false
 */
function swoole_async_read(string $filename, callable $callback, int $chunk_size = -1, int $offset = 0)
{
}

/**
 * 异步写文件
 *
 * 若需要写入的数据大，可分批写入，用户需要设置好每次写入文件的偏移，避免分批写入出现乱序
 *
 * @since 2.0.0
 *
 * @param string   $filename 文件名
 * @param string   $content 待写入文件的数据，数据长度不大于buf_max_len,@swoole_async_set  aio_max_buffer选项
 * @param int      $offset [optional]  写入数据的相对文件起始的偏移量
 * @param callable $callback [optional]  结果回调
 *
 * @return bool 成功返回 true，失败返回 false
 */
function swoole_async_write(string $filename, string $content, int $offset = -1, callable $callback = null)
{
}

/**
 * 将域名解析为IP地址。调用此函数是非阻塞的，调用会立即返回。将向下执行后面的代码。
 * @since 3.0.4
 *
 * @param string   $domain_name 域名
 * @param callable $callback 查询成功后回调的函数
 *
 * @return false|void 参数错误返回 false，否则进行域名查询
 */
function swoole_async_dns_lookup(string $domain_name, callable $callback)
{
}

/**
 * 清除swoole内置的DNS缓存，对swoole_client和swoole_async_dns_lookup 有效。
 * @since 3.0.4
 *
 * @return void
 */
function swoole_clean_dns_cache()
{
}

/**
 * 获取可读/可写/错误文件描述符的列表
 * @since 3.0.4
 *
 * @param &array $read_array 可读文件描述符数组的引用
 * @param &array $write_array 可写文件描述符数组的引用
 * @param &array $error_array 错误文件描述符数组的引用
 * @param float $timeout [optional] 超时时间，单位为秒，可以是浮点数
 *
 * @return int|false 成功返回事件的数量，失败返回false
 */
function swoole_client_select(array &$read_array, array &$write_array, array &$error_array, float $timeout = 0.5)
{
}

/**
 * 设置进程的名称，修改进程名后，ps看到的将是设定的字符串，函数功能同php中的 cli_set_process_title
 * @since 3.0.4
 *
 * @param string $process_name 指定进程的名称
 * ```php
 * 在onStart回调中执行此函数，将修改主进程的名称。在onWorkerStart中调用将修改worker子进程的名称。
 *
 * 如何为Swoole Server重命名各个进程名称
 *  在swoole_server_create之前修改为manager进程名称
 *  onStart调用时修改为主进程名称
 *  onWorkerStart修改为worker进程名称
 * ```
 * @return void
 */
function swoole_set_process_name(string $process_name)
{
}

/**
 * 将标准的Unix Errno 错误码转换字符串表示的错误信息
 * @since 3.0.4
 *
 * @param int $errno
 *
 * @return string 可读的错误信息
 */
function swoole_strerror(int $errno)
{
}

/**
 * 获取最近一次系统调用的错误码，错误码的值与操作系统有关。可是使用swoole_strerror将错误转换为错误信息。
 * @since 3.0.4
 *
 * @return int 返回errno的值
 */
function swoole_errno()
{
}
