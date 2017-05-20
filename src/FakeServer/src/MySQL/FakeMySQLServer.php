<?php

namespace Minimalism\FakeServer\MySQL;


/**
 * Class FakeMySQLServer
 * @package Minimalism\FakeServer\MySQL
 */
class FakeMySQLServer
{
    /**
     * @var \swoole_server
     */
    public $swooleServer;

    private $config;

    /**
     * @var MySQLConnection[]
     */
    private $session;

    // TODO 添加接口 && 默认实现
    public $eventHandler;

    public function __construct(array $config = [])
    {
        $config += [
            "host" => "0.0.0.0",
            "port" => 3306,

            'open_length_check' => 1,
            'package_length_type' => 'v',
            // yz-swoole 不支持...
            /**
             * 返回0，数据不足，需要接收更多数据
             * 返回-1，数据错误，底层会自动关闭连接
             * 返回包长度值，包括包头和包体的总长度，底层会自动将包拼好后返回给回调函数
             *
             * @param string $bin
             * @return int
             */
            // 'package_length_func' => [$this, "mysqlPackageLengthCheck"],
            'package_length_offset' => 0,
            'package_body_offset' => 4,
            
            // 'open_nova_protocol' => 1, // disable
            'package_max_length' => 1024 * 1024 * 16, // mysql max packet size + 1
            
            // 'enable_port_reuse' => true,
            // 'user' => 'www-data',
            // 'group' => 'www-data',
            // 'log_file' => __DIR__.'/simple_server.log',

            'buffer_output_size' => 1024 * 1024 * 16,
            'pipe_buffer_size' => 1024 * 1024 * 16,
            'max_connection' => 10000,
            'max_request' => 100000,

            /**
             * 1平均分配，2按FD取摸固定分配，3抢占式分配，默认为取模(dispatch=2)
             *
             * 1，轮循模式，收到会轮循分配给每一个worker进程
             * 2，固定模式，根据连接的文件描述符分配worker。这样可以保证同一个连接发来的数据只会被同一个worker处理
             * 3，抢占模式，主进程会根据Worker的忙闲状态选择投递，只会投递给处于闲置状态的Worker
             * 4，IP分配，根据客户端IP进行取模hash，分配给一个固定的worker进程。可以保证同一个来源IP的连接数据总会被分配到同一个worker进程。算法为 ip2long(ClientIP) % worker_num
             * 5，UID分配，需要用户代码中调用 $serv-> bind() 将一个连接绑定1个uid。然后swoole根据UID的值分配到不同的worker进程。算法为 UID % worker_num，如果需要使用字符串作为UID，可以使用crc32(UID_STRING)
             *
             * dispatch_mode 4,5两种模式，在 1.7.8以上版本可用
             * dispatch_mode=1/3时，底层会屏蔽onConnect/onClose事件，原因是这2种模式下无法保证onConnect/onClose/onReceive的顺序
             * 非请求响应式的服务器程序，请不要使用模式1或3
             *
             * 抢占式分配，每次都是空闲的worker进程获得数据。很合适SOA/RPC类的内部服务框架
             * 当选择为dispatch=3抢占模式时，worker进程内发生onConnect/onReceive/onClose/onTimer会将worker进程标记为忙，不再接受新的请求。reactor会将新请求投递给其他状态为闲的worker进程
             * 如果希望每个连接的数据分配给固定的worker进程，dispatch_mode需要设置为2
             */
            'dispatch_mode' => 2,
            'open_tcp_nodelay' => 1,
            // 'open_cpu_affinity' => 1,
            // 'daemonize' => 0,

            'reactor_num' => swoole_cpu_num(),
            'worker_num' => swoole_cpu_num(),
        ];

        $this->config = $config;
        
        $this->swooleServer = new \swoole_server($config["host"], $config["port"], SWOOLE_PROCESS, SWOOLE_SOCK_TCP);
        $this->swooleServer->set($config);

        $this->eventHandler = [];
        $this->session = [];
    }

    /**
     * @param string $evt login,...
     * @param callable $action
     */
    public function on($evt, callable $action)
    {
        $this->eventHandler[$evt] = $action;
    }

    public function start()
    {
        $this->swooleServer->on('start', [$this, 'onStart']);
        $this->swooleServer->on('shutdown', [$this, 'onShutdown']);

        $this->swooleServer->on('workerStart', [$this, 'onWorkerStart']);
        $this->swooleServer->on('workerStop', [$this, 'onWorkerStop']);
        $this->swooleServer->on('workerError', [$this, 'onWorkerError']);

        $this->swooleServer->on('connect', [$this, 'onConnect']);
        $this->swooleServer->on('receive', [$this, 'onReceive']);

        $this->swooleServer->on('close', [$this, 'onClose']);

        $this->swooleServer->start();
    }

    public function onConnect(\swoole_server $swooleServer, $fd, $fromId)
    {
        $this->openSession($fd);
    }

    public function onClose(\swoole_server $swooleServer, $fd, $fromId)
    {
        $this->closeSession($fd);
    }

    public function onStart(\swoole_server $swooleServer)
    {
        sys_echo("server start [host={$this->config["host"]}, port={$this->config["port"]}]");
    }

    public function onShutdown(\swoole_server $swooleServer)
    {
        sys_echo("server shutdown");
    }

    public function onWorkerStart(\swoole_server $swooleServer, $workerId)
    {
        $_SERVER["WORKER_ID"] = $workerId;
        sys_echo("worker #$workerId start");
    }

    public function onWorkerStop(\swoole_server $swooleServer, $workerId)
    {
        sys_echo("worker #$workerId stop");
    }

    public function onWorkerError(\swoole_server $swooleServer, $workerId, $workerPid, $exitCode, $sigNo)
    {
        sys_echo("worker error happening [workerId=$workerId, workerPid=$workerPid, exitCode=$exitCode, signalNo=$sigNo]");
    }

    public function onReceive(\swoole_server $swooleServer, $fd, $fromId, $data)
    {
        echo $data, "\n";
        echo bin2hex($data), "\n\n";

        $connection = $this->openSession($fd);
        $connection->write($data);

        while (true) {
            $len = $connection->readPacket();
            if ($len === -1) {
                $this->closeSession($fd);
                break;
            } else if ($len === 0) {
                break;
            }
        }
    }

    public function openSession($fd)
    {
        if (isset($this->session[$fd])) {
            $connection = $this->session[$fd];
        } else {
            $connection = new MySQLConnection($this, $fd);
            $connection->action();
            $this->session[$fd] = $connection;
        }

        return $connection;
    }

    public function closeSession($fd)
    {
        if ($this->swooleServer->exist($fd)) {
            $this->swooleServer->close($fd);
        }
        unset($this->session[$fd]);
    }
}