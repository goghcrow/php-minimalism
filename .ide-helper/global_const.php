<?php

/**
 * global_const
 *
 * @since 3.0.4
 *
 * iniEntries:
 * swoole.aio_thread_num = 2
 * swoole.display_errors = On
 * swoole.use_namespace = Off
 * swoole.message_queue_key = 0
 * swoole.unixsock_buffer_size = 8388608
 *
 * @package global_const
 */


/**
 * 当前 swoole 版本号，字符串类型，如 3.0.4
 *
 * @since 3.0.4
 *
 * @global string
 */
define("SWOOLE_VERSION", "3.0.4");

/**
 * swoole_server 构造函数参数，Server 运行模式，BASE模式，业务代码在reactor进程中执行
 * @since 3.0.4
 * @global int
 */
define("SWOOLE_BASE", 4);

/**
 * swoole_server 构造函数参数，Server 运行模式为进程模式，业务代码在Worker进程中执行
 * @since 3.0.4
 *
 * @global int
 */
define("SWOOLE_PROCESS", 3);

/**
 * @since 3.0.4
 */
define("SWOOLE_PACKET", 16);

/**
 * @since 3.0.4
 * @todo 用法待查证，应该是 worker 和 taks 进程通信方式
 */
define("SWOOLE_IPC_UNSOCK", 1);

/**
 * @since 3.0.4
 * @todo 用法待查证
 */
define("SWOOLE_IPC_MSGQUEUE", 2);

/**
 * socket 类型，tcp ipv4 socket，同 SWOOLE_TCP
 * @since 3.0.4
 * @global int
 */
define("SWOOLE_SOCK_TCP", 1);

/**
 * socket 类型，tcp ipv6 socket，同 SWOOLE_TCP6
 * @since 3.0.4
 * @global int
 */
define("SWOOLE_SOCK_TCP6", 3);

/**
 * socket 类型，udp ipv4 socket，同 SWOOLE_UDP
 * @since 3.0.4
 * @global int
 */
define("SWOOLE_SOCK_UDP", 2);

/**
 * socket 类型，udp ipv6 socket。同 SWOOLE_UDP6
 * @since 3.0.4
 * @global int
 */
define("SWOOLE_SOCK_UDP6", 4);

/**
 * socket 类型，unix socket dgram，同SWOOLE_UNIX_DGRAM
 * @since 3.0.4
 */
define("SWOOLE_SOCK_UNIX_DGRAM", 5);

/**
 * socket 类型，unix socket stream，同 SWOOLE_UNIX_STREAM
 * @since 3.0.4
 */
define("SWOOLE_SOCK_UNIX_STREAM", 6);

/**
 * socket 类型，tcp ipv4 socket，同 SWOOLE_SOCK_TCP
 * @since 3.0.4
 */
define("SWOOLE_TCP", 1);

/**
 * socket 类型，tcp ipv6 socket，同 SWOOLE_SOCK_TCP6
 * @since 3.0.4
 */
define("SWOOLE_TCP6", 3);

/**
 * socket 类型，udp ipv4 socket，同 SWOOLE_SOCK_UDP
 * @since 3.0.4
 */
define("SWOOLE_UDP", 2);

/**
 * socket 类型，udp ipv6 socket，同 SWOOLE_SOCK_UDP6
 * @since 3.0.4
 */
define("SWOOLE_UDP6", 4);

/**
 * socket 类型，unix socket dgram，同 SWOOLE_SOCK_UNIX_DGRAM
 * @since 3.0.4
 */
define("SWOOLE_UNIX_DGRAM", 5);

/**
 * socket 类型，unix socket stream，同 SWOOLE_SOCK_UNIX_STREAM
 * @since 3.0.4
 */
define("SWOOLE_UNIX_STREAM", 6);

/**
 * swoole_client构造函数参数，同步客户端
 * @since 3.0.4
 */
define("SWOOLE_SOCK_SYNC", 0);

/**
 * swoole_client构造函数参数，异步客户端
 * @since 3.0.4
 */
define("SWOOLE_SOCK_ASYNC", 1);

/**
 * SSL 加密方法
 * @since 3.0.4
 */
define("SWOOLE_SSL", 512);

/**
 * SSL 加密方法
 * @since 3.0.4
 */
define("SWOOLE_SSLv3_METHOD", 1);

/**
 * SSL 加密方法
 * @since 3.0.4
 */
define("SWOOLE_SSLv3_SERVER_METHOD", 2);

/**
 * SSL 加密方法
 * @since 3.0.4
 */
define("SWOOLE_SSLv3_CLIENT_METHOD", 3);

/**
 * SSL 加密方法，默认加密方法
 * @since 3.0.4
 */
define("SWOOLE_SSLv23_METHOD", 0);

/**
 * SSL 加密方法
 * @since 3.0.4
 */
define("SWOOLE_SSLv23_SERVER_METHOD", 4);

/**
 * SSL 加密方法
 * @since 3.0.4
 */
define("SWOOLE_SSLv23_CLIENT_METHOD", 5);

/**
 * SSL 加密方法
 * @since 3.0.4
 */
define("SWOOLE_TLSv1_METHOD", 6);

/**
 * SSL 加密方法
 * @since 3.0.4
 */
define("SWOOLE_TLSv1_SERVER_METHOD", 7);

/**
 * SSL 加密方法
 * @since 3.0.4
 */
define("SWOOLE_TLSv1_CLIENT_METHOD", 8);

/**
 * SSL 加密方法
 * @since 3.0.4
 */
define("SWOOLE_TLSv1_1_METHOD", 9);

/**
 * SSL 加密方法
 * @since 3.0.4
 */
define("SWOOLE_TLSv1_1_SERVER_METHOD", 10);

/**
 * SSL 加密方法
 * @since 3.0.4
 */
define("SWOOLE_TLSv1_1_CLIENT_METHOD", 11);

/**
 * @since 3.0.4
 */
define("SWOOLE_TLSv1_2_METHOD", 12);

/**
 * SSL 加密方法
 * @since 3.0.4
 */
define("SWOOLE_TLSv1_2_SERVER_METHOD", 13);

/**
 * SSL 加密方法
 * @since 3.0.4
 */
define("SWOOLE_TLSv1_2_CLIENT_METHOD", 14);

/**
 * SSL 加密方法
 * @since 3.0.4
 */
define("SWOOLE_DTLSv1_METHOD", 15);

/**
 * SSL 加密方法
 * @since 3.0.4
 */
define("SWOOLE_DTLSv1_SERVER_METHOD", 16);

/**
 * SSL 加密方法
 * @since 3.0.4
 */
define("SWOOLE_DTLSv1_CLIENT_METHOD", 17);

/**
 * swoole_event_add 函数参数，读事件类型掩码
 * @since 3.0.4
 */
define("SWOOLE_EVENT_READ", 512);

/**
 * swoole_event_add 函数参数，写事件类型掩码
 * @since 3.0.4
 */
define("SWOOLE_EVENT_WRITE", 1024);


/**
 * 基于线程池模拟实现，文件读写请求投递到任务队列，然后由AIO线程读写文件，完成后通知主线程。
 * AIO线程本身是同步阻塞的。所以并非真正的异步IO。
 * @link https://wiki.swoole.com/wiki/page/183.html
 * @since 3.0.4
 */
define("SWOOLE_AIO_BASE", 0);

/**
 * 基于Linux Native AIO系统调用，是真正的异步IO，并非阻塞模拟。
 * @link https://wiki.swoole.com/wiki/page/183.html
 * @since 3.0.4
 */
define("SWOOLE_AIO_LINUX", 2);

/**
 * websocket 数据帧类型，UTF-8 文本字符数据
 * @since 3.0.4
 */
define("WEBSOCKET_OPCODE_TEXT", 1);

/**
 * websocket 数据帧类型，二进制类型
 * @since 3.0.4
 */
define("WEBSOCKET_OPCODE_BINARY", 2);

/**
 * websocket 连接状态，连接进入等待握手
 * @since 3.0.4
 */
define("WEBSOCKET_STATUS_CONNECTION", 1);

/**
 * websocket 连接状态，正在握手
 * @since 3.0.4
 */
define("WEBSOCKET_STATUS_HANDSHAKE", 2);

/**
 * websocket 连接状态，已握手成功等待浏览器发送数据帧，同 WEBSOCKET_STATUS_ACTIVE
 * @since 3.0.4
 */
define("WEBSOCKET_STATUS_FRAME", 3);

/**
 * websocket 连接状态，已握手成功等待浏览器发送数据帧，同 WEBSOCKET_STATUS_FRAME
 * @since 3.0.4
 */
define("WEBSOCKET_STATUS_ACTIVE", 3);
