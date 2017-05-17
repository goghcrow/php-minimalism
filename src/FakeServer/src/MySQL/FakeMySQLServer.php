<?php

namespace Minimalism\FakeServer\MySQL;


class FakeMySQLServer
{
    /**
     * @var \swoole_server
     */
    public $swooleServer;

    public $config;

    /**
     * @var MySQLConnection[]
     */
    public $session;

    public $eventHandler = [];

    public function __construct(array $config = [])
    {
        $config += [
            "host" => "0.0.0.0",
            "port" => 3306,

            'open_length_check' => 1,
            'package_length_type' => 'v',
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

            // TODO 这里有问题
            // 'dispatch_mode' => 3, // 不会触发on connect/close
            'open_tcp_nodelay' => 1,
            // 'open_cpu_affinity' => 1,
            // 'daemonize' => 0,
            
            // 'reactor_num' => 2,
            'worker_num' => 2,
        ];

        $this->eventHandler += [
            "login" => function() { return true; }
        ];

        $this->config = $config;
        
        $this->swooleServer = new \swoole_server($config["host"], $config["port"], SWOOLE_PROCESS, SWOOLE_SOCK_TCP);
        $this->swooleServer->set($config);

        $this->session = [];
    }

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
        $connection = new MySQLConnection($this, $fd);
        $connection->action();
        $this->session[$fd] = $connection;
    }

    public function onClose(\swoole_server $swooleServer, $fd, $fromId)
    {
        unset($this->session[$fd]);
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
        if (isset($this->session[$fd])) {
            $connection = $this->session[$fd];
            $connection->inputBuffer->write($data);
            $connection->action();
        } else {
            $swooleServer->close($fd);
        }
    }
}