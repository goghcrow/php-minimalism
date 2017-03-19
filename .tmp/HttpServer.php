<?php
/*
CREATE TABLE `swoole_version` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hostname` varchar(50) NOT NULL DEFAULT '',
  `ip` int(10) unsigned NOT NULL DEFAULT '0',
  `swoole_ver` varchar(10) NOT NULL,
  `ct` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `u_idx` (`hostname`,`ip`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;
 */

(new HttpServer())->listen(3000);


//$cmd = "tcpdump -i any -s 0 -U -w - 2> /dev/null";
//proc_open($cmd, [0 => ["pipe", "r"]], $pipes);
//swoole_event_add($pipes[0], function($stdin)  {
//    stream_get_contents($stdin);
//});
//exit;

// /bin/bash -c "ulimit -Sc unlimited; exec ...."


class HttpServer
{
    /**
     * @var \swoole_http_server
     */
    public $httpServer;

    public function __construct()
    {
    }

    public function defaultConfig()
    {
        return [
            "host" => "0.0.0.0",
            "ssl" => false,
            "max_connection" => 10240,
            'max_request' => 100000,
            'dispatch_mode' => 3,
            "open_tcp_nodelay" => 1,
            "open_cpu_affinity" => 1,
            "daemonize" => 0,
            "reactor_num" => 1,
            "worker_num" => \swoole_cpu_num(),
        ];
    }

    public function listen($port = 8000, array $config = [])
    {
        $config = ['port' => $port] + $config + $this->defaultConfig();
        $this->httpServer = new \swoole_http_server($config['host'], $config['port'], SWOOLE_PROCESS, SWOOLE_SOCK_TCP);
        $this->httpServer->set($config);
        $this->bindEvent();
        $this->httpServer->start();
    }

    protected function bindEvent()
    {
        $this->httpServer->on('start', [$this, 'onStart']);
        $this->httpServer->on('shutdown', [$this, 'onShutdown']);
        $this->httpServer->on('connect', [$this, 'onConnect']);
        $this->httpServer->on('close', [$this, 'onClose']);

        $this->httpServer->on('workerStart', [$this, 'onWorkerStart']);
        $this->httpServer->on('workerStop', [$this, 'onWorkerStop']);
        $this->httpServer->on('workerError', [$this, 'onWorkerError']);
        $this->httpServer->on('request', [$this, 'onRequest']);

        // output buffer overflow, reactor will block, dont wait
        \swoole_async_set(["socket_dontwait" => 1]);
        socket_set_option($this->httpServer->getSocket(), SOL_SOCKET, SO_REUSEADDR, 1);
    }

    public function onConnect(\swoole_http_server $httpServer)
    {
        sys_echo(__FUNCTION__);
    }

    public function onClose(\swoole_http_server $httpServer)
    {
        sys_echo(__FUNCTION__);
    }

    public function onStart(\swoole_http_server $httpServer)
    {
        sys_echo(__FUNCTION__);
    }

    public function onShutdown(\swoole_http_server $httpServer)
    {
        sys_echo(__FUNCTION__);
    }

    public function onWorkerStart(\swoole_http_server $httpServer, $workerId)
    {
        $_ENV["WORKER_ID"] = $workerId;
        sys_echo("worker #$workerId start");
    }

    public function onWorkerStop(\swoole_http_server $httpServer, $workerId)
    {
        sys_echo("worker #$workerId stop");
    }

    public function onWorkerError(\swoole_http_server $httpServer, $workerId, $workerPid, $exitCode, $sigNo)
    {
        sys_error("worker error happen [workerId=$workerId, workerPid=$workerPid, exitCode=$exitCode, signalNo=$sigNo]");
    }

    public function onRequest(\swoole_http_request $req, \swoole_http_response $res)
    {
        if (isset($req->get) && is_array($req->get) && isset($req->get["ver"])) {
            $hostname = isset($req->get["host"]) ? addslashes($req->get["host"]) : "";
            $ip = isset($req->get["ip"]) ? (ip2long($req->get["ip"]) ?: 0) : 0;
            $swVer = addslashes($req->get["ver"]);
            $sql = <<<SQL
insert into swoole_version (`hostname`, `ip`, `swoole_ver`, `ct`) value ("$hostname", $ip, "$swVer", NOW())
on duplicate key update `swoole_ver` = "$swVer", `ct` = NOW()
SQL;
            sys_echo($sql);
            $this->mysqlQuery($sql, function($mysql, $result) {});
        }

        $res->status(200);
        $res->end();
    }

    public function mysqlQuery($sql, callable $onQuery)
    {
        $mysql = new \swoole_mysql();
        $mysql->on("close", function() { });
        $mysql->connect([
            "host" => "10.9.34.172",
            "port" => 3306,
            "user" => "uuid",
            "password" => "uuid",
            "database" => "uuid",
            "charset" => "utf8mb4",
        ], function(\swoole_mysql $mysql, $result) use($sql, $onQuery) {
            if ($result) {
                $mysql->query($sql, $onQuery);
            } else {
                sys_error("connect error [errno=$mysql->connect_errno, error=$mysql->connect_error]");
            }
        });
    }
}

function sys_echo($context) {
    // $_SERVER 会被swoole setglobal 清空, 这里用 $_ENV
    $workerId = isset($_ENV["WORKER_ID"]) ? $_ENV["WORKER_ID"] : "";
    $time = date("Y-m-d H:i:s", time());
    echo "[$time #$workerId] $context\n";
}

function sys_error($context) {
    $workerId = isset($_ENV["WORKER_ID"]) ? $_ENV["WORKER_ID"] : "";
    $time = date("Y-m-d H:i:s", time());
    fprintf(STDERR, "[$time #$workerId] $context\n");
}