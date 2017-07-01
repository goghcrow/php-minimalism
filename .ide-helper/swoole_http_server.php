<?php

/**
 * class swoole_http_server
 *
 * @since 3.0.4
 *
 * @package swoole_http_server
 */

/**
 * class swoole_http_server extends swoole_server
 * 继承自 swoole_server 类
 *
 * @since 3.0.4
 *
 */
class swoole_http_server/* extends \swoole_server*/
{
    /**
     * @var int $master_pid 前服务主进程 master 的 PID
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
     * @internal
     */
    public $pid = "";

    /**
     * @internal
     */
    public $id = 0;

    /**
     * @internal
     */
    public $host = "";

    /**
     * @internal
     */
    public $port = -1;

    /**
     * @internal
     */
    public $type = 0;

    /**
     * @internal
     */
    public $mode = 0;

    /**
     * @internal
     */
    public $ports;

    /**
     * swoole_server::set() 函数所设置的参数会保存到 swoole_server::$setting 属性上。在回调函数中可以访问运行参数的值。
     * @var array 数组，保存通过 set() 设置的参数属性列表
     */
    public $setting;

    /**
     * @var callable TCP 连接迭代器，可以使用 foreach 遍历服务当前所有的连接，
     *                   此属性的功能与swoole_server->connnection_list是一致的，但是更加友好，遍历的元素为单个连接的fd。
     */
    public $connections;

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
     * worker 进程收到完整的 http 请求后调用此函数，函数原型为：
     *
     * function (swoole_http_request $request, swoole_http_response $response);
     *
     * @var callable 收到 http 请求回调函数
     * @internal
     */
    public $onRequest;

    /**
     * __construct swoole_http_server 构造函数
     *
     * @since 3.0.4
     *
     * @param string $serv_host 服务要监听的 ip 地址
     * @param int    $serv_port 服务要监听的 port 端口
     *
     */
    public function __construct(string $serv_host, int $serv_port)
    {
    }

    /**
     * on 设置 swoole_http_server 事件回调函数
     *
     * @since 3.0.4
     *
     * @param $event_name
     * @param $callback
     *
     * ```php
     * swoole_http_server 事件列表
     * Start    master 进程，master 进程启动后调用
     * Shutdown master 进程，master 进程退出
     *
     * ManagerStart manager 进程，manager 进程启动
     * ManagerStop  manager 进程，manager 进程退出
     * WorkerError  manager 进程，worker 进程出错
     *
     * Close    worker 进程，客户端关闭连接事件
     * Error    worker 进程，客户端连接错误
     * Finish   worker 进程，task 进程调用 finish 接口或 return，worker 进程触发该事件
     *
     * WorkerStart worker/task 进程，worker/task 进程启动
     * WorkerStop  worker/task 进程，worker/task 进程退出
     * PipeMessage worker/task 进程，调用 $server->sendMessage 接口后，收到消息的进程触发该事件
     *
     * Task        task 进程，worker 进程向 task 进程发送数据
     *
     * Request   worker 进程，服务端收到一个完整的 http 请求后，调用此函数。onRequest 回调会销毁 $request 和 $response 对象
     * $http_server->on('request', function ($request, $response) {
     *      $response->end("<h1>Hello Swoole. #".rand(1000, 9999)."</h1>");
     * });
     * ```
     *
     * @return
     */
    public function on(string $event_name, callable $callback)
    {
    }

    /**
     * start 启动 Http 服务器
     *
     * @since 3.0.4
     *
     * @return bool 启动成功返回 true，失败返回 false
     */
    public function start()
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
     * set 设置 swoole_http_server 的配置选项
     *
     * @since 3.0.4
     *
     * @link  https://wiki.swoole.com/wiki/page/13.html
     *
     * @param array $zset 数组，key->value 形式的配置项
     * ```php
     * 参考 swoole_server->set 中配置选项，http_server 额外增加了如下配置：
     * upload_tmp_dir  设置上传文件的临时目录
     * http_parse_post 设置POST消息解析开关，选项为true时自动将Content-Type为x-www-form-urlencoded的请求包体解析到POST数组。
     *                 设置为false时将关闭POST解析。
     * ```
     * @return bool 成功返回true，失败返回 false
     */
    public function set(array $zset)
    {
    }


    /**
     * exist 检测 $fd 对应的连接是否存在
     *
     * @since 3.0.4
     *
     * @param int $conn_fd 要检测的连接的 $fd
     *
     * @return bool 连接存在返回 true，不存在返回 false
     */
    public function exist(int $conn_fd)
    {
    }

    /**
     * protect 从心跳管理中隔离连接，用于保护某些连接不被心跳线程切断
     *
     * @since 3.0.4
     *
     * @param  int  $conn_fd 要保护的连接 $fd
     * @param  bool $is_protected [optional] 可选参数，默认为 true
     *
     * @return bool 成功返回 true，失败返回 false
     */
    public function protect(int $conn_fd, bool $is_protected = null)
    {
    }

    /**
     * sendfile 发送文件到 TCP 客户端连接
     * sendfile 函数调用 OS 提供的 sendfile 系统调用，由操作系统直接读取文件并写入 socket。
     * sendfile 只有2次内存拷贝，使用此函数可以降低发送大量文件时操作系统的 CPU 和内存占用。
     *
     * @since 3.0.4
     *
     * @param int    $conn_fd 指定目的客户端
     * @param string $filename 要发送的文件路径，如果文件不存在会返回 false
     *
     * @return bool 操作成功返回 true，失败返回 false
     */
    public function sendfile(int $conn_fd, string $filename)
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
    public function task(mixed $data, int $worker_id)
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
     * @return int 返回一个整型数字错误码
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
     * @return void
     */
    public function heartbeat(int $if_close_connection = false)
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
    public function getClientInfo(int $fd, int $from_id)
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
     * denyRequest worker process 拒绝服务?
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
     * exit
     *
     * @since 3.0.4
     *
     * @return
     */
    //public function exit(){}

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
     * ```php
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
