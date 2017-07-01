<?php
/**
 * class swoole_server
 *
 * @since 3.0.4
 *
 * @package swoole_server
 */

/**
 * class swoole_server
 *
 * @since 3.0.4
 *
 */
class swoole_server
{
    /**
     * @var int $master_pid 前服务主进程 master 的 PID
     * @property-read
     */
    public $master_pid = 0;

    /**
     * @var int $manager_pid 当前服务管理进程 manager 的 PID
     */
    public $manager_pid = 0;

    /**
     * @var int 当前 Worker 进程的操作系统进程ID。与posix_getpid()的返回值相同。
     */
    public $worker_pid = 0;


    /**
     * @var bool $taskworker true表示当前进程是Task工作进程，false表示当前进程是Worker进程
     */
    public $taskworker;

    /**
     * @var int
     * @internal
     */
    public $pid = "";

    /**
     * @var int
     * @internal
     * /
     * public $id = 0;
     *
     * /**
     * @var string 服务监听的 ip 地址
     */
    public $host = "";

    /**
     * @var int 服务监听的端口
     */
    public $port = -1;

    /**
     * @var int
     * @internal
     */
    public $type = 0;

    /**
     * @var int
     * @internal
     */
    public $mode = 0;

    /**
     * swoole_server::set() 函数所设置的参数会保存到 swoole_server::$setting 属性上。在回调函数中可以访问运行参数的值。
     * @var array 数组，保存通过 set() 设置的参数属性列表
     */
    public $setting;

    /**
     * @internal
     */
    public $ports;

    /**
     * 有新的连接进入时，在worker进程中回调。函数原型：
     *
     * function onConnect(swoole_server $server, int $fd, int $from_id);
     *
     *                    $server 是 swoole_server 对象。
     *                    $fd 是连接的文件描述符，发送数据/关闭连接时需要此参数。
     *                    $from_id 来自那个 Reactor 线程。
     * @var callable 连接事件回调函数
     * @internal
     */
    public $onConnect;

    /**
     * 接收到数据时回调此函数，发生在worker进程中。函数原型:
     *
     * function onReceive(swoole_server $server, int $fd, int $from_id, string $data);
     *
     *                    $server，swoole_server对象。
     *                    $fd，TCP客户端连接的文件描述符。
     *                    $from_id，TCP连接所在的Reactor线程ID。
     *                    $data，收到的数据内容，可能是文本或者二进制内容。
     * @var callable 接收到数据事件回调函数
     * @internal
     */
    public $onReceive;

    /**
     * TCP客户端连接关闭后，在worker进程中回调此函数。函数原型：
     *
     * function onClose(swoole_server $server, int $fd, int $reactorId);
     *
     *                  $server 是swoole_server对象。
     *                  $fd 是连接的文件描述符。
     *                  $reactorId 来自那个reactor线程。
     * @var callable 连接关闭事件回调函数
     * @internal
     */
    public $onClose;

    /**
     * 接收到UDP数据包时回调此函数，发生在worker进程中。函数原型：
     *
     * function onPacket(swoole_server $server, string $data, array $client_info);
     *
     *                   $server，swoole_server对象。
     *                   $data，收到的数据内容，可能是文本或者二进制内容。
     *                   $client_info，客户端信息包括address/port/server_socket 3项数据。
     * 服务器同时监听TCP/UDP端口时，收到TCP协议的数据会回调onReceive，收到UDP数据包回调onPacket
     * @var callable 接收到 UDP 数据包事件回调函数
     * @internal
     */
    public $onPacket;

    /**
     * Server启动在主进程的主线程回调此函数，函数原型：
     *
     * function onStart(swoole_server $server);
     *
     * 在此事件之前Swoole Server已进行了如下操作：
     *      已创建了manager进程。
     *      已创建了worker子进程。
     *      已监听所有TCP/UDP端口。
     *      已监听了定时器。
     * @var callable 主进程启动完成事件回调函数
     * @internal
     */
    public $onStart;

    /**
     * 此事件在Server结束时发生，函数原型：
     *
     * function onShutdown(swoole_server $server);
     *
     * 在此之前Swoole Server已进行了如下操作：
     *      已关闭所有线程。
     *      已关闭所有worker进程。
     *      已close所有TCP/UDP监听端口。
     *      已关闭主Rector。
     * @var callable server 结束事件回调函数
     * @internal
     */
    public $onShutdown;

    /**
     * 此事件在worker进程/task进程启动时发生。这里创建的对象可以在进程生命周期内使用。原型：
     *
     * function onWorkerStart(swoole_server $server, int $worker_id)
     *
     * 可以通过$worker_id参数的值来，判断worker是普通worker还是task_worker。
     * $worker_id>= $serv->setting['worker_num'] 时表示这个进程是task_worker。
     * $worker_id是一个从0-$worker_num之间的数字，表示这个worker进程的ID，$worker_id和进程PID没有任何关系
     * @var callable worker/task 进程启动成功回调函数
     * @internal
     */
    public $onWorkerStart;

    /**
     * 此事件在worker进程终止时发生。在此函数中可以回收worker进程申请的各类资源。原型：
     *
     * function onWorkerStop(swoole_server $server, int $worker_id);
     *
     * @var callable worker/task 进程退出事件回调函数
     * @internal
     */
    public $onWorkerStop;

    /**
     * 在task_worker进程内被调用。worker进程可以使用swoole_server->task函数向task_worker进程投递新的任务。
     * 当前的Task进程在调用onTask回调函数时会将进程状态切换为忙碌，这时将不再接收新的Task，
     * 当onTask函数返回时会将进程状态切换为空闲然后继续接收新的Task。函数原型为：
     *
     * function onTask(swoole_server $serv, int $task_id, int $src_worker_id, mixed $data);
     *
     * @var callable task 进程收到 worker 进程发送的任务回调函数
     * @internal
     */
    public $onTask;

    /**
     * 当 worker 进程投递的任务在task_worker中完成时，task进程会通过swoole_server->finish()方法将任务处理的结果发送给worker进程。
     * 函数原型为：
     *
     * void onFinish(swoole_server $serv, int $task_id, string $data)；
     *
     *      $task_id是任务的ID，
     *      $data是任务处理的结果内容。
     * @var callable worker task 进程调用 finish 或者 return 返回数据后，worker 进程回调此函数
     * @internal
     */
    public $onFinish;

    /**
     * 当 worker/task_worker 进程发生异常后会在 Manager 进程内回调此函数。函数原型为：
     *
     * void onWorkerError(swoole_server $serv, int $worker_id, int $worker_pid, int $exit_code, int $signal);
     *
     *      $worker_pid 是异常进程的ID。
     *      $exit_code 退出的状态码，范围是 1 ～255。
     *      $signal 进程退出的信号。
     * @var callable worker/task 进程发生异常时回调函数
     * @internal
     */
    public $onWorkerError;

    /**
     * 当管理进程启动时调用它，函数原型：
     *
     * void onManagerStart(swoole_server $serv);
     * 在这个回调函数中可以修改管理进程的名称
     *
     * @var callable Manager 进程启动成功后回调函数
     * @internal
     */
    public $onManagerStart;

    /**
     * 当管理进程结束时调用它，函数原型：
     *
     * void onManagerStop(swoole_server $serv);
     *
     * @var callable Manager 进程退出回调函数
     * @internal
     */
    public $onManagerStop;

    /**
     * 当工作进程收到由sendMessage发送的管道消息时会触发onPipeMessage事件。worker/task进程都可能会触发onPipeMessage事件。函数原型：
     *
     * void onPipeMessage(swoole_server $server, int $from_worker_id, string $message);
     *
     * @var callable worker/task进程收到 sendMessage 发送的消息回调函数
     * @internal
     */
    public $onPipeMessage;

    /**
     * @var callable TCP 连接迭代器，可以使用 foreach 遍历服务当前所有的连接，
     *                   此属性的功能与swoole_server->connnection_list是一致的，但是更加友好，遍历的元素为单个连接的fd。
     */
    public $connections;

    /**
     * __construct 创建一个 swoole_server 对象
     *
     * @since 3.0.4
     *
     * @param string $serv_host 指定服务要监听的 ip 地址，如 127.0.0.1，0.0.0.0表示监听本机全部 ip 地址
     * @param int    $serv_port 指定服务要监听的端口，如 9501
     * @param int    $serv_mode [optional] 可选参数，服务运行模式，默认为多进程模式 SWOOLE_PROCESS
     *                                     SWOOLE_BASE Base模式，业务代码在Reactor进程中直接执行
     *                                     SWOOLE_PROCESS 进程模式，业务代码在Worker进程中执行
     * @param int    $sock_type [optional] 可选参数，指定 socket的类型，默认为 TCP，支持 TCP/UDP、TCP6/UDP6、UinxSock Stream/Dgram 6种
     * ```php
     *  SWOOLE_SOCK_TCP/SWOOLE_TCP
     *  SWOOLE_SOCK_UDP/SWOOLE_UDP
     *  SWOOLE_SOCK_TCP6/SWOOLE_TCP6
     *  SWOOLE_SOCK_UDP6/SWOOLE_UDP6
     *  SWOOLE_SOCK_UNIX_STREAM/SWOOLE_SOCK_UNIX_STREAM
     *  SWOOLE_SOCK_UNIX_DGRAM/SWOOLE_UNIX_DGRAM
     *
     *  使用$sock_type|SWOOLE_SSL可以启用SSL加密，SLL 相关的加密算法常量见全局常量定义
     * ```
     */
    public function __construct(string $serv_host, int $serv_port, int $serv_mode = SWOOLE_PROCESS, $sock_type = SWOOLE_SOCK_TCP)
    {
    }

    /**
     * 增加一个Server要监听的端口
     *
     * @since 3.0.4
     *
     * @param string $host 要监听的ip地址
     * @param int    $port 要监听的端口
     * @param int    $sock_type socket类型，参考构造函数中的 $sock_type
     *
     * @return \swoole_server_port|false 成功返回 swoole_server 对象，失败返回 false
     */
    public function listen(string $host, int $port, int $sock_type)
    {
    }

    /**
     * 增加一个Server要监听的端口，意义同 listen 接口
     *
     * @since 3.0.4
     *
     * @param string $host 要监听的ip地址
     * @param int    $port 要监听的端口
     * @param int    $sock_type socket类型，参考构造函数中的 $sock_type
     *
     * @return \swoole_server_port|false 成功返回 swoole_server 对象，失败返回 false
     */
    public function addlistener(string $host, int $port, int $sock_type)
    {
    }

    /**
     * 注册 Server 的事件回调函数
     *
     * @since 3.0.4
     *
     * @param string   $event_name 事件名，不区分大小写
     * ```php
     * swoole_server 事件列表
     * Start    master 进程，master 进程启动后调用
     * Shutdown master 进程，master 进程退出
     *
     * ManagerStart manager 进程，manager 进程启动
     * ManagerStop  manager 进程，manager 进程退出
     * WorkerError  manager 进程，worker 进程出错
     *
     * Connect  worker 进程，客户端连接事件
     * Receive  worker 进程，收到客户端发送的数据
     * Close    worker 进程，客户端关闭连接事件
     * Packet   worker 进程，Udp server 收到客户端发送数据
     * Error    worker 进程，客户端连接错误
     *
     * Task     task 进程，worker 进程向 task 进程发送数据
     * Finish   worker 进程，task 进程调用 finish 接口或 return，worker 进程触发该事件
     *
     * //receive 事件回调示例
     * $server->on('Receive', function ($serv, $fd, $from_id, $data) {
     *      $serv->send($fd, 'Swoole: '.$data);
     * });
     *
     * WorkerStart worker/task 进程，worker/task 进程启动
     * WorkerStop  worker/task 进程，worker/task 进程退出
     * PipeMessage worker/task 进程，调用 $server->sendMessage 接口后，收到消息的进程触发该事件
     *
     * //在 worker 进程启动时创建一个时隔3秒的定时器
     * $timer_id = null;
     * $server->on('WorkerStart', function ($serv, $worker_id){
     *      if (0 == $worker_id) {
     *          swoole_timer_tick(3000, function(){
     *              echo "timer_id: $timer_id" . "\n";
     *          });
     *      }
     * });
     * ```
     * @param callable $callback 发生 $event_name 事件时的回调函数
     *
     * @return bool 成功返回 true，失败返回 false
     */
    public function on(string $event_name, callable $callback)
    {
    }

    /**
     * set
     *
     * @since 3.0.4
     *
     * @link  https://wiki.swoole.com/wiki/page/13.html
     * @link  https://wiki.swoole.com/wiki/page/274.html
     *
     * @param array $zset set tcp server arguments
     *```php
     * //示例如下：
     * $server->set(array(
     *      'reactor_num' => 2,     //reactor thread num
     *      'worker_num' => 4,      //worker process num
     *      'backlog' => 128,       //listen backlog
     *      'max_request' => 1000,
     *      'dispatch_mode' => 1,
     * ));
     *
     * reactor_num      reactor 线程数，一般为 CPU 核心数的 1-4 倍，默认会启用和 CPU 核数相同的数量
     * worker_num       work 进程数
     * dispatch_mode    数据包分发策略，默认为 2；1. 轮循模式，数据会轮循分配给每一个 worker 进程；
     *                  2. 固定模式，每一个连接发来的数据只会被一个 worker 进程处理；3.抢占模式，投递给处于闲置状态的 worker 进程；
     *                  4. IP分配，根据客户端ip进行取模hash，分配给一个固定的worker进程，可以保证同一个ip连接的数据总会分配给一个固定的worker进程；
     *                  5. UID 分配，需要用户代码中调用 $serv->bind()将一个连接绑定到一个 uid，server 根据uid的值分配数据；
     * dispatch_func    设置dispatch函数，swoole底层了内置了5种dispatch_mode，如果仍然无法满足需求。
     *
     * max_request      work 进程能处理的最大任务数，work 进程处理完超过此数值的任务后将自动退出
     * max_conn         服务端允许的最大连接数
     * task_worker_num  task 进程的数量，启用 task 功能，必须注册 onTask/onFinish 事件
     * task_ipc_mode    task 进程与 worker 进程之间的通信方式；1. unix socket 通信，默认；2.消息队列；3.消息队列，争抢模式；
     * task_max_request task 进程能处理的最大任务数，task 进程处理完超过此数值的任务后自动退出
     * daemonize        将进程转为守护化进程，长时间运行的服务端程序须设置此选项
     * backlog          listen 队列长度，此参数将决定同时有多少个等待 accept 的连接
     *
     * open_eof_check   打开EOF检测，此选项将检测客户端连接发来的数据，当数据包结尾是指定的字符串时才会投递给Worker进程。
     *                  否则会一直拼接数据包，直到超过缓存区或者超时才会中止。
     * open_eof_split   启用EOF自动分包。当设置open_eof_check后，底层检测数据是否以特定的字符串结尾来进行数据缓冲。
     *                  但默认只截取收到数据的末尾部分做对比。这时候可能会产生多条数据合并在一个包内。启用open_eof_split参数后，
     *                  底层会从数据包中间查找EOF，并拆分数据包。onReceive每次仅收到一个以EOF字串结尾的数据包。
     * package_eof      与 open_eof_check 或者 open_eof_split 配合使用，设置EOF字符串。
     * open_length_check   打开包长检测特性。包长检测提供了固定包头+包体这种格式协议的解析。启用后，可以保证Worker进程onReceive每次都会收到一个完整的数据包。
     * package_length_type 长度值的类型，接受一个字符参数，与php的pack函数一致。目前swoole支持10种类型：
     *                      c：有符号、1字节
     *                      C：无符号、1字节
     *                      s ：有符号、主机字节序、2字节
     *                      S：无符号、主机字节序、2字节
     *                      n：无符号、网络字节序、2字节
     *                      N：无符号、网络字节序、4字节
     *                      l：有符号、主机字节序、4字节（小写L）
     *                      L：无符号、主机字节序、4字节（大写L）
     *                      v：无符号、小端字节序、2字节
     *                      V：无符号、小端字节序、4字节
     * package_length_func 设置长度解析函数，支持C++或PHP的2种类型的函数。长度函数必须返回一个整数。
     * package_max_length  设置最大数据包尺寸，单位为字节。开启open_length_check/open_eof_check/open_http_protocol等协议解析后。
     *                     swoole底层会进行数据包拼接。这时在数据包未收取完整时，所有数据都是保存在内存中的。
     *
     * task_tmpdir      设置task的数据临时目录，server 中如果投递的数据超过 8192 字节，将会启用临时文件保存数据
     * message_queue_key 设置消息队列的 key，仅在 task_ipc_mode = 2/3 时使用；
     * log_file          指定 swoole_server 的错误日志，默认打印到屏幕
     * log_level         swooler_server 的错误日志打印级别，范围是 0-5，低于 log_level 的日志信息不会抛出
     * heartbeat_check_interval 启用心跳检测，单位为秒；如 heartbeat_check_interval => 60，表示每60秒，遍历所有连接，
     *                           如果该连接在60秒内，没有向服务器发送任何数据，此连接将被强制关闭。
     * heartbeat_idle_time 与heartbeat_check_interval配合使用。表示连接最大允许空闲的时间。
     *
     * open_cpu_affinity   启用CPU亲和性设置。在多核的硬件平台中，启用此特性会将swoole的reactor线程/worker进程绑定到固定的一个核上。
     * cpu_affinity_ignore IO密集型程序中，所有网络中断都是用CPU0来处理，如果网络IO很重，CPU0负载过高会导致网络中断无法及时处理，那网络收发包的能力就会下降。
     *                     如果不设置此选项，swoole将会使用全部CPU核，底层根据reactor_id或worker_id与CPU核数取模来设置CPU绑定。
     *                     如果内核与网卡有多队列特性，网络中断会分布到多核，可以缓解网络中断的压力。此选项必须与open_cpu_affinity同时设置才会生效
     * open_tcp_nodelay    启用open_tcp_nodelay，开启后TCP连接发送数据时会关闭Nagle合并算法，立即发往客户端连接。在某些场景下，如http服务器，可以提升响应速度。
     * tcp_defer_accept    启用tcp_defer_accept特性，可以设置为一个数值，单位为秒，表示当一个TCP连接有数据发送时延后多少秒才触发accept。
     *
     * ssl_cert_file       设置SSL隧道加密，设置值为一个文件名字符串，制定cert证书和key私钥的路径。
     * ssl_method          设置OpenSSL隧道加密的算法。Server与Client使用的算法必须一致，否则SSL/TLS握手会失败，连接会被切断。 默认算法为 SWOOLE_SSLv23_METHOD
     * ssl_ciphers         启用SSL后，设置ssl_ciphers来改变openssl默认的加密算法
     *
     * user                设置worker/task子进程的所属用户。服务器如果需要监听1024以下的端口，必须有root权限。但程序运行在root用户下，代码中一旦有漏洞，
     *                     攻击者就可以以root的方式执行远程指令，风险很大。配置了user项之后，可以让主进程运行在root权限下，子进程运行在普通用户权限下。
     * group               设置worker/task子进程的进程用户组。与user配置相同，此配置是修改进程所属用户组，提升服务器程序的安全性。
     * chroot              重定向Worker进程的文件系统根目录。此设置可以使进程对文件系统的读写与实际的操作系统文件系统隔离。提升安全性。
     * pid_file            在Server启动时自动将master进程的PID写入到文件，在Server关闭时自动删除PID文件。
     * pipe_buffer_size    调整管道通信的内存缓存区长度。Swoole使用Unix Socket实现进程间通信。
     * buffer_output_size  配置发送缓存区尺寸。
     * enable_unsafe_event swoole在配置dispatch_mode=1或3后，因为系统无法保证onConnect/onReceive/onClose的顺序，默认关闭了onConnect/onClose事件。
     *                     如果应用程序需要onConnect/onClose事件，并且能接受顺序问题可能带来的安全风险，
     *                     可以通过设置enable_unsafe_event为true，启用onConnect/onClose事件
     * discard_timeout_request 在配置dispatch_mode=1或3后，系统无法保证onConnect/onReceive/onClose的顺序，因此可能会有一些请求数据在连接关闭后，才能到达Worker进程。
     * enable_reuse_port   设置端口重用，此参数用于优化TCP连接的Accept性能，启用端口重用后多个进程可以同时进行Accept操作。
     *
     * enable_delay_receive 设置此选项为true后，accept客户端连接后将不会自动加入EventLoop，仅触发onConnect回调。
     *                      worker进程可以调用$serv->confirm($fd)对连接进行确认，此时才会将fd加入EventLoop开始进行数据收发，
     *                      也可以调用$serv->close($fd)关闭此连接。
     * open_http_protocol   启用Http协议处理，swoole_http_server 会自动启用此选项。设置为false表示关闭Http协议处理。
     * open_http2_protocol  启用HTTP2协议解析，需要依赖--enable-http2编译选项。默认为false
     * open_websocket_protocol 启用Http协议处理，swoole_websocket_server 会自动启用此选项。设置为false表示关闭Http协议处理。
     *                         设置open_websocket_protocol选项为true后，会自动设置open_http_protocol协议也为true。
     * open_mqtt_protocol      启用mqtt协议处理，启用后会解析mqtt包头，worker进程onReceive每次会返回一个完整的mqtt数据包。
     * ```
     * @return bool 成功返回true，失败返回 false
     */
    public function set(array $zset)
    {
    }

    /**
     * 启动 server，并监听端口，启动成功后会创建worker_num+2个进程。主进程+Manager进程+worker_num个Worker进程。
     *
     * @since 3.0.4
     *
     * @return bool 服务启动成功返回true，失败返回false
     */
    public function start()
    {
    }

    /**
     * 向指定客户端发送数据
     *
     * @since 3.0.4
     *
     * @param int    $fd 目的客户端 $fd
     *               TCP 服务，$fd 保存的是 socket fd
     *               UDP 服务，$fd 保存的是客户端 ip(网络字节序)，$from_id 保存 port
     * @param string $send_data 要发送的数据
     * @param int    $from_id [optional] 可选参数，用于指定 reactor 线程 id
     *
     * @return bool 发送成功会返回true，发送失败会返回false
     */
    public function send(int $fd, string $send_data, int $from_id = null)
    {
    }

    /**
     * sendto 向任意的客户端 ip:port 发送 UDP 数据包
     *
     * @since 3.0.4
     *
     * @param string $ip 目标 ip 地址
     * @param int    $port 目标端口
     * @param string $send_data 发送数据
     * @param int    $server_sock [optional] 可选参数，默认 -1 选第一个。指定使用哪个 udp 端口发送数据，服务器可能监听多个 UDP 端口
     *
     * @return bool 发送成功返回 true，失败或参数错误返回 false
     */
    public function sendto(string $ip, int $port, string $send_data, int $server_sock = -1)
    {
    }

    /**
     * exist 检测 $fd 对应的连接是否存在
     *
     * @since 3.0.4
     *
     * @param int $fd 要检测的连接的 $fd
     *
     * @return bool 连接存在返回 true，不存在返回 false
     */
    public function exist(int $fd)
    {
    }

    /**
     * protect 从心跳管理中隔离连接，用于保护某些连接不被心跳线程切断
     *
     * @since 3.0.4
     *
     * @param  int  $fd 要保护的连接 $fd
     * @param  bool $is_protected [optional] 可选参数，默认为 true
     *
     * @return bool 成功返回 true，失败返回 false
     */
    public function protect(int $fd, bool $is_protected = true)
    {
    }

    /**
     * sendfile 发送文件到 TCP 客户端连接
     * sendfile 函数调用 OS 提供的 sendfile 系统调用，由操作系统直接读取文件并写入 socket。
     * sendfile 只有2次内存拷贝，使用此函数可以降低发送大量文件时操作系统的 CPU 和内存占用。
     *
     * @since 3.0.4
     *
     * @param int    $fd 指定目的客户端
     * @param string $filename 要发送的文件路径，如果文件不存在会返回 false
     *
     * @return bool 操作成功返回 true，失败返回 false
     */
    public function sendfile(int $fd, string $filename)
    {
    }

    /**
     * close  关闭客户端连接
     *
     * @since 3.0.4
     *
     * @param int  $fd 要关闭的客户端连接
     * @param bool $reset [optional] 是否强制关闭，默认为否，设置为 true 会强制关闭连接，丢弃发送队列中的数据
     *
     * @return bool 操作成功返回 true，失败返回 false
     */
    public function close(int $fd, bool $reset = false)
    {
    }

    /**
     * task 非阻塞投递任务到task_worker 池子中，此函数是非阻塞的，执行完毕会立即返回，worker进程可以继续处理新的请求。
     *
     * @since 3.0.4
     *
     * @param mixed $data 非资源类型的任意 php 变量
     * @param int   $worker_id [optional]  可选参数，默认值为 -1。投递目标task_worker 进程id，默认 －1，设置范围 0 - (serv->task_worker_num -1)
     *
     * @return int|bool 调用成功，返回值为整数 $task_id，表示此任务的 ID。如果有 finish 回应，onFinish 回调中会携带 $task_id 参数
     *                  调用失败，返回 false
     */
    public function task(mixed $data, int $worker_id = -1)
    {
    }

    /**
     * 用于在 task 进程中通知 worker 进程，投递的任务已完成，此函数可以传递结果数据给 worker 进程
     * 要在 task 进程中调用 finish 函数，必须为 Server 设置 onFinish 回调函数。此函数只可用于 task 进程的 onTask 回调中
     * 在 task 进程中，return 数据同样会触发 onFinish 事件。
     *
     * @since 3.0.4
     *
     * @param mixed $data task 进程要传递给 worker 进程的数据
     *
     * @return bool 成功返回 true，失败返回 false
     */
    public function finish(mixed $data)
    {
    }

    /**
     * reload  重启所有worker 进程
     *
     * @since 3.0.4
     * @link https://wiki.swoole.com/wiki/page/p-server/reload.html
     *
     * @param bool $only_reload_taskworkrer [option] 是否仅重启 task 进程
     *
     * @return bool 调用成功返回 true，调用失败返回 false
     */
    public function reload(bool $only_reload_taskworkrer = false)
    {
    }

    /**
     * shutdown  关闭服务器，可以用在worker 进程内。向主进程发送SGITERM也可以实现关闭服务器。
     *
     * @since 3.0.4
     *
     * @return void
     */
    public function shutdown()
    {
    }

    /**
     * stop  停止当前worker进程，并立即触发onWorkerStop回调函数，停止其它worker进程需要指定 $worker_id 的值。
     *
     * @since 3.0.4
     *
     * @param int $worker_id ［optional］可选参数，停止自己可以不用设置，如果要停止其它worker进程，需通过参数指定 $worker_id 指定进程号
     *
     * @return bool 调用成功返回 true，调用失败返回 false
     */
    public function stop(int $worker_id = null)
    {
    }

    /**
     * getLastError 获取最近一次操作错误的错误码
     *
     * @since 3.0.4
     *
     * @link https://wiki.swoole.com/wiki/page/554.html
     * @return int 返回一个整型数字错误码，常见发送失败错误如下：
     * ```
     * 1001 连接已经被Server端关闭了，出现这个错误一般是代码中已经执行了$serv->close()关闭了某个连接，但仍然调用$serv->send()向这个连接发送数据
     * 1002 连接已被Client端关闭了，Socket已关闭无法发送数据到对端
     * 1003 正在执行close，onClose回调函数中不得使用$serv->send()
     * 1004 连接已关闭
     * 1005 连接不存在，传入$fd 可能是错误的
     * 1007 接收到了超时的数据，TCP关闭连接后，可能会有部分数据残留在管道缓存区内，这部分数据会被丢弃
     * 1008 发送缓存区已满无法执行send操作，出现这个错误表示这个连接的对端无法及时收数据导致发送缓存区已塞满
     * ```
     */
    public function getLastError()
    {
    }

    /**
     * 检测服务器所有连接，并找出已经超过约定时间的连接。
     * 如果if_close_connection=true，则自动关闭超时的连接。否则仅返回连接的fd数组。
     *
     * @since 3.0.4
     *
     * @param bool $if_close_connection [optional] 可选参数，默认为 false，
     *
     * @return bool
     */
    public function heartbeat(bool $if_close_connection = false)
    {
    }

    /**
     * 该函数用来获取连接的信息
     *
     * @since 3.0.4
     *
     * @param int  $fd woker 进程的编号
     * @param int  $from_id [optional] 可选参数，若设置该参数，则只获取通过 reactor 线程 id 为 $from_id 的连接信息
     * @param bool $ignore_close [optional] 可选参数，默认值为 false，如果设置为 true，即使连接关闭也会返回连接信息
     *
     * @return array|false 如果传入的 fd 存在，将会返回一个数组。如果传入的 fd 不存在或已关闭，返回 false。
     *         如果 $ignore_close=true，即使连接关闭也返回连接的信息。输出结果示例如下：
     * ```
     * Array
     * (
     * [server_fd] => 3
     * [socket_type] => 1
     * [server_port] => 9501
     * [remote_port] => 56119
     * [remote_ip] => 127.0.0.1
     * [from_id] => 0
     * [connect_time] => 1495691486
     * [last_time] => 1495691486
     * )
     * ```
     */
    public function getClientInfo(int $fd, int $from_id = -1, bool $ignore_close = false)
    {
    }

    /**
     * 遍历当前Server所有的客户端连接，getClientList 方法是基于共享内存的，不存在IOWait，遍历的速度很快。
     * 另外 getClientList 会返回所有 TCP 连接，而不仅仅是当前 worker 进程的 TCP 连接。
     *
     * @since 3.0.4
     *
     * @param int $start_fd [optional] 起始 $fd，默认是 0
     * @param int $find_count [optional] 每页取多少条，默认是 10，最大值不得超过 SW_MAX_FIND_COUNT，即100
     *
     * @return array|false 调用成功返回一个数字索引，元素是取到的 $fd。数组会按从小到大排序，
     *         最后一个 $fd 作为新的 start_fd 再次尝试获取，调用失败返回 false
     */
    public function getClientList(int $start_fd = 0, int $find_count = 10)
    {
    }

    /**
     * denyRequest worker_id 进程不接受新请求
     *
     * @since 3.0.4
     *
     * @param int $worker_id 指定worker id
     *
     * @return bool|void 失败返回 false
     * @todo 具体语义待查证
     */
    public function denyRequest(int $worker_id)
    {
    }

    /**
     * exit 退出子进程
     *
     * @since 3.0.4
     * @param int $status 进程退出状态
     * @return void
     */
    //无法生成文档
    //public function exit(int $status=0){}

    /**
     * 向任意 worker 进程或着 tasker 进程发送消息。不能在主进程和管理进程中使用，收到消息的进程会触发 onPipeMessage 事件
     *
     * @since 3.0.4
     *
     * @param string $msg 要发送的消息内容
     * @param int    $work_id 目标进程 id，范围是0 ~ (worker_num + task_worker_num - 1)
     *
     * @return bool 成功返回 true，失败返回 false
     */
    public function sendMessage(string $msg, int $work_id)
    {
    }

    /**
     * addProcess  添加一个用户自定义的工作进程
     *
     * @since 3.0.4
     *
     * @param swoole_process $process
     *
     * @return int|false 成功返回该工作进程的 worker id，失败返回false
     *
     */
    public function addProcess(swoole_process $process)
    {
    }

    /**
     * stats 统计当前server 活动tcp 的连接数／启动时间／接受、关闭次数等信息
     *
     * @since 3.0.4
     *
     * @return array 返回 server 的统计信息，示例如下：
     * ```
     * Array
     * (
     * [start_time] => 1495680500
     * [connection_num] => 1
     * [accept_count] => 1
     * [close_count] => 0
     * [tasking_num] => 0
     * [request_count] => 0
     * [worker_request_count] => 0
     * )
     * ```
     */
    public function stats()
    {
    }

    /**
     * getSocket 获取监听套接字的socket句柄
     * 此方法需要依赖PHP的sockets扩展，并且编译swoole时需要开启--enable-sockets选项
     *
     * @since 3.0.4
     *
     * @param int $port [optional] 指定端口，不填表示不指定，则取第一个监听的套接字
     *
     * @return resource 返回 sockets 资源句柄
     */
    public function getSocket(int $port = null)
    {
    }

    /**
     * bind 将连接 $fd 绑定到用户自定义$uid,设置 dispatch_mode=5，设置此 $uid 值进行 hash 固定分配，
     * 可以保证某一个 $uid 的连接全部会分配到同一个Worker进程。
     *
     * @since 3.0.4
     *
     * @link https://wiki.swoole.com/wiki/page/369.html
     *
     * @param int $fd 要连接的文件描述符
     * @param int $uid 用户自定义的 UID
     *
     * @return bool 成功返回 true，失败返回 false
     */
    public function bind(int $fd, int $uid)
    {
    }

}

