#!/usr/bin/env php
<?php

// 使用jmeter将日志文件导出报告
// ~/apache-jmeter-3.1/bin/jmeter -g report.jtl -o ./report
// cd report
// php -S 0.0.0.0:9999


// 自行修改 payload_factory 生成测试payload 与 receive_assert 响应段颖

// 修改 payload_factory 函数
// 可以使用 tcp 方式测试


$usage = <<<USAGE
Usage: ab -x类型 -h主机 -p端口 -c并发数 -n请求数 -t超时 -l测试名称 
          -x http
          -x tcp
          -x nova -s服务 -m方法 -aJson参数
          -x mysql -w密码 -b数据库 -r字符编码

    ab -x http -h 127.0.0.1 -p 80 -c 200 -n 200000   
    ab -x tcp -h 127.0.0.1 -p 9001 -c 200 -n 200000
    ab -x mysql -h 127.0.0.1 -p 3306 -u showcase -w showcase -b showcase -r utf8mb4 -c 200 -n 200000
    ab -x nova -h 127.0.0.1 -p 8050 -s com.youzan.material.general.service.MediaService -m getMediaList -a '{"query":{"categoryId":2,"xxxId":1,"pageNo":1,"pageSize":5}}'
USAGE;

$opt = getopt('x:h:p:c:n:t:l:s:m:a:u:w:b:r:');


if (!is_array($opt) || !isset($opt['x']) ||
    !in_array($type = $opt['x'], ["tcp", "http", "nova", "mysql"], true)) {
    echo "\033[1m$usage\033[0m\n";
    exit(1);
}

if (!isset($opt['h']) || !isset($opt['p'])) {
    echo "$type required -h -p\n";
    echo "\033[1m$usage\033[0m\n";
    exit(1);
}

if ($type === "nova" && (!isset($opt["m"]) || !isset($opt["s"]))) {
    echo "nova required -m -s\n";
    echo "\033[1m$usage\033[0m\n";
    exit(1);
}

switch ($type) {
    /** @noinspection PhpMissingBreakStatementInspection */
    case "tcp":
        $l = isset($opt['l']) ? $opt['l'] : "tcp-test"; // test label

        function payload_factory(\swoole_client $client) {
            static $cache;
            if ($cache === null) {
                // $cache = "GET /loading_test.html HTTP/1.1\r\nConnection: keep-alive\r\n\r\n";
                $cache = tcp_payload(2);
            }
            return $cache;
        };

        function receive_assert(\swoole_client $client, $recv) {
            return true;
        }

        break;

    /** @noinspection PhpMissingBreakStatementInspection */
    case "http":
        $l = isset($opt['l']) ? $opt['l'] : "http-test"; // test label

        function payload_factory(\swoole_http_client $client) {
            static $cache;
            if ($cache === null) {
                $cache = [
                    "uri"     => "/",
                    "method"  => "GET",
                    "headers" => ["Connection" => "keep-alive"],
                    "cookies" => [],
                    "body"    => "",
                ];
            }

            return $cache;
        };

        function receive_assert(\swoole_http_client $client) {
            // echo substr($client->body, 0, 10), "\n";
            return $client->statusCode === 200;
        }
        break;

    /** @noinspection PhpMissingBreakStatementInspection */
    case "nova":
        $l = isset($opt['l']) ? $opt['l'] : "nova-test"; // test label
        $s = isset($opt['s']) ? $opt['s'] : "com.youzan.service.test"; // nova service;
        $m = isset($opt['m']) ? $opt['m'] : "ping"; // nova method;
        $a = isset($opt['a']) ? $opt['a'] : "{}"; // json args

        $args = json_decode($a, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "\033[1;31m", "JSON参数有误: ", json_last_error_msg(), "\033[0m\n";
            exit(1);
        }
        $attach = [];

        function payload_factory(\swoole_client $client) {
            global $s, $m, $args, $attach;
            static $cache;
            if ($cache === null) {
                $cache =  nova::packNova($client, $s, $m, $args, $attach);
            }
            return $cache;
        }

        function receive_assert(\swoole_client $client, $recv) {
            $res = nova::unpackResponse($recv);
            // print_r($res);
            return true;
        }
        break;

    case "mysql":
        $l = isset($opt['l']) ? $opt['l'] : "mysql-test";
        $user = isset($opt['u']) ? $opt['u'] : "root";
        $password = isset($opt['w']) ? $opt['w'] : "";
        $database = isset($opt['b']) ? $opt['b'] : "test";
        $charset = isset($opt['r']) ? $opt['r'] : "utf8mb4";

        function payload_factory(\swoole_mysql $client) {
            static $cache;
            if ($cache === null) {
//                $cache =  "select * from component_v2";
                $cache =  "select 1";
            }
            return $cache;
        }

        function receive_assert(\swoole_mysql $client, $recv) {
            //  print_r($recv); // TODO
            return true;
        }
        break;
}

$h = $opt['h']; // ip
$p = $opt['p']; // port
$c = isset($opt['c']) ? $opt['c'] : 200; // Concurrency
$n = isset($opt['n']) ? $opt['n'] : null; // requests
$t = isset($opt['t']) ? $opt['t'] : null; // timeout, null 不设置超时


echo "type=$type, ip=$h, port=$p, c=$c, n=$n, timeo=$t\n";

$proc_num = swoole_cpu_num() * 2;
$concurrency = intval(ceil($c / $proc_num)); // 进程均分Concurrency,
if ($n === null) {
    $requests = null;
} else {
    $requests = intval(ceil($n / $proc_num / $concurrency)); // 进程内每个连接均分requests,
}

echo "proc=$proc_num, n/conn=$requests, conn/proc=$concurrency\n";

$conf = [
    "ip"   => $h,
    "host" => $h,
    "port" => $p,

    "payload_factory" => "payload_factory",
    "receive_assert" => "receive_assert",

    "connect_timeout" => $t,
    "recv_timeout" => $t,

    "requests" => $requests,
    "proc_num" => $proc_num,
    "concurrency" => $concurrency,

    "label" => $l,
];


if ($type === "nova") {
    $conf = $conf + [
            "open_length_check" => 1,
            "package_length_type" => 'N',
            "package_length_offset" => 0,
            "package_body_offset" => 0,
            "open_nova_protocol" => 1,
            "socket_buffer_size" => 1024 * 1024 * 2,
        ];
}

if ($type === "mysql") {
    $conf = $conf + [
            "user" => $user,
            "password" => $password,
            "database" => $database,
            "charset" => $charset,
        ];
}


fprintf(STDERR, json_encode($conf, JSON_PRETTY_PRINT) . "\n");
ab::start($type, $conf);



class ab
{
    /**
     * @var tcp_client|http_client[]
     */
    public static $clients = [];

    public static $pids = [];

    public static $reports = [];

    public static $conf;

    public static $enable;

    public static function start($type, array $conf)
    {
        self::$conf = $conf;

        $label = self::$conf["label"];

        for ($i = 0; $i < self::$conf["proc_num"]; $i++) {

            $report_file = "{$label}_{$i}.jtl";

            $pid = pcntl_fork();
            if ($pid < 0) {
                fprintf(STDERR, "fork fail");
                exit(1);
            }



            else if ($pid === 0) {

                ini_set("memory_limit", "1024M");
                swoole_async_set([
                    "thread_num" => 5,
                    "aio_max_buffer" => 1024 * 1024 * 10,
                ]);

                pcntl_signal(SIGTERM, function() {
                    foreach (self::$clients as $client) {
                        $client->stop();
                    }
                    report::stop();
                });

                report::start(self::$conf["label"], $report_file, $i);

                for ($j = 0; $j < self::$conf["concurrency"]; $j++) {
                    switch ($type) {
                        case "http":
                            self::$clients[] = new http_client(self::$conf);
                            break;
                        case "nova":
                        case "tcp":
                            self::$clients[] = new tcp_client(self::$conf);
                            break;
                        case "mysql":
                            self::$clients[] = new mysql_client(self::$conf);
                            break;
                    }
                }

                exit(0);
            }


            self::$pids[] = $pid;
            self::$reports[] = $report_file;
        }

        pcntl_signal(SIGINT, function() {
            foreach (self::$pids as $pid) {
                posix_kill($pid, SIGTERM);
            }
            self::$enable = false;
        });

        self::$enable = true;
        self::loop();

        self::merge_result("report.jtl");
    }

    public static function loop()
    {
        while (self::$enable) {
            usleep(200 * 1000);
            pcntl_signal_dispatch();
        }

        foreach (self::$pids as $pid) {
            pcntl_waitpid($pid, $status);
            if (!pcntl_wifexited($status)) {
                fprintf(STDERR, "$pid %s exit [exit_status=%d, stop_sig=%d, term_sig=%d]\n",
                    pcntl_wifexited($status) ? "normal": "abnormal",
                    pcntl_wexitstatus($status),
                    pcntl_wstopsig($status),
                    pcntl_wtermsig($status)
                );
            }
        }
    }

    public static function merge_result($file)
    {
        $title = "timeStamp,elapsed,label,responseCode,responseMessage,threadName,dataType," .
            "success,failureMessage,bytes,sentBytes,grpThreads,allThreads,Latency,IdleTime,Connect";

        // TODO 清洗数据
        `echo '$title' > $file`;
        foreach (self::$reports as $report) {
            `cat $report >> $file`;
            unlink($report);
        }
    }
}


class tcp_client
{
    /** @var \swoole_client */
    public $client;

    public $lastTs;

    public $timer;

    public $send;

    public $recv;

    public $conf;

    public $enable;

    public function __construct(array $conf)
    {
        $this->conf = $conf + [
                "ip"   => "127.0.0.1",
                "port" => 80,
                "payload_factory" => function() { return "GET /loading_test.html HTTP/1.1\r\n" .
                        "Connection: keep-alive\r\n\r\n"; },
                "receive_assert" => function() { return true; },
                "connect_timeout" => null,
                "recv_timeout" => null,
                "requests" => null,
            ];
        $this->enable = true;
        $this->reconnect(false);
    }

    public function __call($name, $arguments)
    {
        $m = $this->client->$name;
        return $m(...$arguments);
    }

    public function __get($name)
    {
        return $this->client->$name;
    }

    public function reconnect($fail = true)
    {
        $this->cancelTimer();

        if ($fail) {
            $this->fail();
        }

        if ($this->enable === false) {
            return;
        }

        $this->client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
        $this->client->set($this->conf);
        $this->client->on("connect", [$this, "onConnect"]);
        $this->client->on("close", [$this, "onClose"]);
        $this->client->on("error", [$this, "onError"]);
        $this->client->on("receive", [$this, "onReceive"]);
        $this->addTimer($this->conf["connect_timeout"], [$this, "onTimeout"]);

        $r = $this->client->connect($this->conf["ip"], $this->conf["port"]);
        if (!$r) {
            $this->onError();
        }
    }

    public function send()
    {
        $createPayload = $this->conf["payload_factory"];
        $this->send = $createPayload($this->client);

        $this->lastTs = report::now();
        $this->addTimer($this->conf["recv_timeout"], [$this, "onTimeout"]);

        $len = $this->client->send($this->send);
        if ($len) {
            pcntl_signal_dispatch();
        } else {
            $this->onError();
        }
    }

    public function stop()
    {
        $this->client->close();
        $this->enable = false;
    }

    public function onConnect(\swoole_client $client)
    {
        $this->cancelTimer();

        if ($client->errCode) {
            $this->onError();
        } else {
            $this->send();
        }
    }

    public function onReceive(\swoole_client $client, $recv)
    {
        $this->cancelTimer();
        $this->tickRequest();

        if ($client->errCode) {
            $this->onError();
        } else {
            $this->recv = $recv;

            $recvAssert = $this->conf["receive_assert"];
            if ($recvAssert($client, $recv)) {
                $this->success();
            } else {
                $this->fail();
            }
            $this->send();
        }
    }

    public function onTimeout()
    {
        $this->tickRequest();
        $this->client->close();
        $this->reconnect();
    }

    public function onError()
    {
        $errno = $this->client->errCode;
        $error = socket_strerror($this->client->errCode);
        fprintf(STDERR, "error: [errno=$errno, errno=$error]");
        $this->reconnect();
    }

    public function onClose()
    {
        $errno = $this->client->errCode;
        $error = socket_strerror($this->client->errCode);
        fprintf(STDERR, "close: [errno=$errno, errno=$error]");
        $this->reconnect();
    }

    public function addTimer($after, $onTimeout)
    {
        if ($after !== null) {
            $this->timer = swoole_timer_after($after, $onTimeout);
        }
    }

    public function cancelTimer()
    {
        if ($this->timer) {
            swoole_timer_clear($this->timer);
            $this->timer = null;
        }
    }

    public function tickRequest()
    {
        pcntl_signal_dispatch();

        if ($this->conf["requests"] !== null && --$this->conf["requests"] <= 0) {
            $this->stop();
            posix_kill(posix_getppid(), SIGINT);
        }
    }

    public function success()
    {
        $elapsed = report::now() - $this->lastTs;
        $sentBytes = strlen($this->send);
        $bytes = strlen($this->recv);
        report::success($elapsed, $bytes, $sentBytes);
    }

    public function fail()
    {
        $elapsed = report::now() - $this->lastTs;
        $sentBytes = strlen($this->send);
        $bytes = strlen($this->recv);
        $msg = str_replace(",", ";", socket_strerror($this->client->errCode));
        report::fail($elapsed, $bytes, $sentBytes, $msg);
    }
}

class http_client
{
    /** @var \swoole_http_client */
    public $client;

    public $lastTs;

    public $timer;

    public $send;

    public $recv;

    public $conf;

    public $enable;

    public function __construct(array $conf)
    {
        $this->conf = $conf + [
                "ip"   => "127.0.0.1",
                "port" => 80,

                "payload_factory" => function(\swoole_http_client $client) {
                    return [
                        "uri"     => "/",
                        "method"  => "GET",
                        "headers" => [],
                        "cookies" => [],
                        "body"    => "",
                    ];
                },
                "receive_assert" => function() { return true; },
                "connect_timeout" => null,
                "recv_timeout" => null,
                "requests" => null,
            ];
        $this->enable = true;
        $this->reconnect(false);
    }

    public function __call($name, $arguments)
    {
        $m = $this->client->$name;
        return $m(...$arguments);
    }

    public function __get($name)
    {
        return $this->client->$name;
    }

    public function reconnect($fail = true)
    {
        $this->cancelTimer();

        if ($fail) {
            $this->fail();
        }

        if ($this->enable === false) {
            return;
        }
        
        $this->client = new \swoole_http_client($this->conf["ip"], $this->conf["port"]);
        
        $this->client->set($this->conf);
        $this->client->on("connect", [$this, "onConnect"]);
        $this->client->on("close", [$this, "onClose"]);
        $this->client->on("error", [$this, "onError"]);

        $this->send();
    }

    public function send()
    {
        $createPayload = $this->conf["payload_factory"];
        $payload = $createPayload($this->client);

        $this->client->setMethod($payload["method"]);

        // TODO setHeaders([])  coredump
        if (!empty($payload["headers"])) {
            // TODO coredump
            $this->client->setHeaders($payload["headers"]);
        }
        // TODO 释放了 $payload["cookies"] zval
        if (!empty($payload["cookies"])) {
            $this->client->setCookies($payload["cookies"]);
        }
        $this->client->setData($payload["body"]);

        $this->send = $payload["body"];

        $this->lastTs = report::now();
        $this->addTimer($this->conf["recv_timeout"], [$this, "onTimeout"]);

        $r = $this->client->execute($payload["uri"], [$this, "onReceive"]);
        if ($r) {
            pcntl_signal_dispatch();
        } else {
            $this->onError();
        }
    }

    public function stop()
    {
        $this->client->close();
        $this->enable = false;
    }

    public function onConnect(\swoole_http_client $client)
    {
        if ($client->errCode) {
            $this->onError();
        }
    }

    public function onReceive(\swoole_http_client $client)
    {
        $this->cancelTimer();
        $this->tickRequest();

        if ($client->errCode) {
            $this->onError();
        } else {
            $this->recv = $client->body;

            $recvAssert = $this->conf["receive_assert"];
            if ($recvAssert($client)) {
                $this->success();
            } else {
                $this->fail();
            }

            if ($this->client->isConnected()) {
                $this->send();
            } else {
                $this->reconnect(false);
            }
        }
    }

    public function onTimeout()
    {
        $this->tickRequest();
        $this->client->close();
        $this->reconnect();
    }

    public function onError()
    {
        $errno = $this->client->errCode;
        $error = socket_strerror($this->client->errCode);
        fprintf(STDERR, "error: [errno=$errno, errno=$error]");
        $this->reconnect();
    }

    public function onClose()
    {
        $errno = $this->client->errCode;
        $error = socket_strerror($this->client->errCode);
        fprintf(STDERR, "close: [errno=$errno, errno=$error]");
        $this->reconnect();
    }

    public function addTimer($after, $onTimeout)
    {
        if ($after !== null) {
            $this->timer = swoole_timer_after($after, $onTimeout);
        }
    }

    public function cancelTimer()
    {
        if ($this->timer) {
            swoole_timer_clear($this->timer);
            $this->timer = null;
        }
    }

    public function tickRequest()
    {
        pcntl_signal_dispatch();

        if ($this->conf["requests"] !== null && --$this->conf["requests"] <= 0) {
            $this->stop();
            posix_kill(posix_getppid(), SIGINT);
        }
    }

    public function success()
    {
        $elapsed = report::now() - $this->lastTs;
        $sentBytes = strlen($this->send);
        $bytes = strlen($this->recv);
        report::success($elapsed, $bytes, $sentBytes);
    }

    public function fail()
    {
        $elapsed = report::now() - $this->lastTs;
        $sentBytes = strlen($this->send);
        $bytes = strlen($this->recv);
        $msg = str_replace(",", ";", socket_strerror($this->client->errCode));
        // 兼容旧版本 swoole
        $code = isset($this->client->statusCode) ? $this->client->statusCode : 500;
        report::fail($elapsed, $bytes, $sentBytes, $msg, $code);
    }
}

class mysql_client
{
    /** @var \swoole_mysql */
    public $client;

    public $lastTs;

    public $timer;

    public $send;

    public $recv;

    public $conf;

    public $enable;

    public function __construct(array $conf)
    {
        $this->conf = $conf + [
                "host" => "127.0.0.1",
                "port" => 3306,
                "user" => "root",
                "password" => "",
                "database" => "test",
                "charset" => "utf8mb4",
            ];
        $this->enable = true;
        $this->reconnect(false);
    }

    public function __call($name, $arguments)
    {
        $m = $this->client->$name;
        return $m(...$arguments);
    }

    public function __get($name)
    {
        return $this->client->$name;
    }

    public function reconnect($fail = true)
    {
        $this->cancelTimer();

        if ($fail) {
            $this->fail();
        }

        if ($this->enable === false) {
            return;
        }

        $this->client = new \swoole_mysql();
        $this->client->on("connect", [$this, "onConnect"]);
        $this->client->on("close", [$this, "onClose"]);
        $this->client->on("error", [$this, "onError"]);
        $this->addTimer($this->conf["connect_timeout"], [$this, "onTimeout"]);

        // fprintf(STDERR, "CONNECT\n");
        $r = $this->client->connect([
            "host" => $this->conf["host"],
            "port" => $this->conf["port"],
            "user" => $this->conf["user"],
            "password" => $this->conf["password"],
            "database" => $this->conf["database"],
            "charset" => $this->conf["charset"],
        ]);

        if (!$r) {
            $this->onError();
        }
    }

    public function send()
    {
        $createPayload = $this->conf["payload_factory"];
        $this->send = $createPayload($this->client);

        $this->lastTs = report::now();
        $this->addTimer($this->conf["recv_timeout"], [$this, "onTimeout"]);

        $r = $this->client->query($this->send, [], [$this, "onReceive"]);
        if ($r) {
            pcntl_signal_dispatch();
        } else {
            $this->onError();
        }
    }

    public function stop()
    {
        $this->client->close();
        $this->enable = false;
    }

    public function onConnect(\swoole_mysql $client)
    {
        // fprintf(STDERR, "onConnect\n");
        $this->cancelTimer();

        if ($client->errno) {
            $this->onError();
        } else {
            $this->send();
        }
    }

    public function onReceive(\swoole_mysql $client, $recv)
    {
        $this->cancelTimer();
        $this->tickRequest();

        if ($client->errno) {
            $this->onError();
        } else {
            $this->recv = $recv;

            $recvAssert = $this->conf["receive_assert"];
            if ($recvAssert($client, $recv)) {
                $this->success();
            } else {
                $this->fail();
            }
            $this->send();
        }
    }

    public function onTimeout()
    {
        // fprintf(STDERR, "onTimeout\n");
        $this->tickRequest();
        $this->client->close();
        $this->reconnect();
    }

    public function onError()
    {
        // fprintf(STDERR, "onError\n");
        fprintf(STDERR, "errno={$this->client->errno}, error={$this->client->error}\n");
        $this->reconnect();
    }

    public function onClose()
    {
        // fprintf(STDERR, "onClose\n");
        $this->reconnect();
    }

    public function addTimer($after, $onTimeout)
    {
        if ($after !== null) {
            $this->timer = swoole_timer_after($after, $onTimeout);
        }
    }

    public function cancelTimer()
    {
        if ($this->timer) {
            swoole_timer_clear($this->timer);
            $this->timer = null;
        }
    }

    public function tickRequest()
    {
        pcntl_signal_dispatch();

        if ($this->conf["requests"] !== null && --$this->conf["requests"] <= 0) {
            $this->stop();
            posix_kill(posix_getppid(), SIGINT);
        }
    }

    public function success()
    {
        $elapsed = report::now() - $this->lastTs;
        $sentBytes = strlen($this->send);
        $bytes = 0; // TODO $this->recv mysql query 返回值不确定
        report::success($elapsed, $bytes, $sentBytes);
    }

    public function fail()
    {
        $elapsed = report::now() - $this->lastTs;
        $sentBytes = strlen($this->send);
        $bytes = 0; // TODO $this->recv mysql query 返回值不确定
        $msg = str_replace(",", ";", $this->client->error);
        report::fail($elapsed, $bytes, $sentBytes, $msg, $this->client->errno);
    }
}

class report
{
    private static $offset = 0;
    private static $report;
    private static $report_last;
    private static $file;
    private static $label;
    private static $enable;
    private static $pid;
    private static $nthChild;

    public static function start($label, $file, $nthChild)
    {
        self::$label = $label;
        self::$file = $file;
        self::$pid = posix_getpid();
        self::$report_last = self::now();
        self::$nthChild = $nthChild;
        self::$enable = true;
        self::summary();
    }

    public static function stop()
    {
        self::$enable = false;
    }

    public static function success($elapsed, $bytes, $sentBytes)
    {
        self::$report[] = [
            'timeStamp' => self::now(),
            'elapsed' => $elapsed,
            'label' => self::$label,
            'responseCode' => 200,
            'responseMessage' => 'OK',
            'threadName' => 'thread 1-1',
            'dataType' => 'text',
            'success' => 'true',
            'failureMessage' => '',
            'bytes' => $bytes,
            'sentBytes' => $sentBytes,
            'grpThreads' => 1,
            'allThreads' => 1,
            'Latency' => 0,
            'IdleTime' => 0,
            'Connect' => 0,
        ];
    }

    public static function fail($elapsed, $bytes, $sentBytes, $msg, $code = 500)
    {
        self::$report[] = [
            'timeStamp' => self::now(),
            'elapsed' => $elapsed,
            'label' => self::$label,
            'responseCode' => $code,
            'responseMessage' => 'FAIL',
            'threadName' => 'thread 1-1',
            'dataType' => 'text',
            'success' =>'false',
            'failureMessage' => $msg,
            'bytes' => $bytes,
            'sentBytes' => $sentBytes,
            'grpThreads' => 1,
            'allThreads' => 1,
            'Latency' => 0,
            'IdleTime' => 0,
            'Connect' => 0,
        ];
    }

    private static function summary()
    {
        swoole_timer_after(2000, function() {
            $now = self::now();


            $elapsed = $now - self::$report_last;
            $requests = count(self::$report);
            if ($requests === 0) {
                $avg_res_time = 0;
            } else {
                $avg_res_time = number_format($elapsed / $requests, 2);
            }
            $qps = intval($requests / $elapsed * 1000);
            $pid = self::$pid;
            $summary = "pid=$pid, qps=$qps, avg=$avg_res_time\n";
            fprintf(STDERR, $summary);

            if (empty(self::$report)) {
                self::summary();
            } else {
                $r = [];
                foreach (self::$report as $item) {
                    $r[] = implode(",", array_values($item));
                }

                self::$report = [];
                self::$report_last = $now;
                $log = implode("\n", $r) . "\n";

                self::write($log, function() {
                    if (self::$enable) {
                        self::summary();
                    } else {
                        // 保证日志文件罗盘
                        swoole_event_exit();
                    }
                });
            }
        });
    }

    private static function write($contents, callable $cb)
    {
        file_put_contents2(self::$file, $contents, self::$offset, $cb);
        self::$offset += strlen($contents);
    }

    public static function now()
    {
        return intval(microtime(true) * 1000);
    }
}


class nova
{
    private static $ver_mask = 0xffff0000;
    private static $ver1 = 0x80010000;

    private static $t_call  = 1;
    private static $t_reply  = 2;
    private static $t_ex  = 3;

    /**
     * @param string $recv
     * @return array
     */
    public static function unpackResponse($recv)
    {
        list($response, $attach) = self::unpackNova($recv);
        $res = $err_res = null;
        if (isset($response["error_response"])) {
            $err_res = $response["error_response"];
        } else {
            $res = $response["response"];
        }
        return [$res, $err_res, $attach];
    }

    /**
     * @param swoole_client $client
     * @param string $service
     * @param string $method
     * @param array $args
     * @param array $attach
     * @return string
     */
    public static function packNova(\swoole_client $client, $service, $method, array $args, array $attach)
    {
        $args = self::packArgs($args);
        $thriftBin = self::packThrift($service, $method, $args);
        $attach = json_encode($attach, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $sockInfo = $client->getsockname();
        $localIp = ip2long($sockInfo["host"]);
        $localPort = $sockInfo["port"];

        $return = "";
        $ok = nova_encode("Com.Youzan.Nova.Framework.Generic.Service.GenericService", "invoke",
            $localIp, $localPort,
            nova_get_sequence(),
            $attach, $thriftBin, $return);
        assert($ok);
        return $return;
    }

    /**
     * @param string $raw
     * @return array
     */
    private static function unpackNova($raw)
    {
        $service = $method = $ip = $port = $seq = $attach = $thriftBin = null;
        $ok = nova_decode($raw, $service, $method, $ip, $port, $seq, $attach, $thriftBin);
        assert($ok);

        $attach = json_decode($attach, true, 512, JSON_BIGINT_AS_STRING);

        $response = self::unpackThrift($thriftBin);
        $response = json_decode($response, true, 512, JSON_BIGINT_AS_STRING);
        assert(json_last_error() === 0);

        return [$response, $attach];
    }


    /**
     * @param string $buf
     * @return string
     */
    private static function unpackThrift($buf)
    {
        $read = function($n) use(&$offset, $buf) {
            static $offset = 0;
            assert(strlen($buf) - $offset >= $n);
            $offset += $n;
            return substr($buf, $offset - $n, $n);
        };

        $ver1 = unpack('N', $read(4))[1];
        if ($ver1 > 0x7fffffff) {
            $ver1 = 0 - (($ver1 - 1) ^ 0xffffffff);
        }
        assert($ver1 < 0);
        $ver1 = $ver1 & self::$ver_mask;
        assert($ver1 === self::$ver1);

        $type = $ver1 & 0x000000ff;
        $len = unpack('N', $read(4))[1];
        $name = $read($len);
        $seq = unpack('N', $read(4))[1];
        assert($type !== self::$t_ex); // 不应该透传异常
        // invoke return string
        $fieldType = unpack('c', $read(1))[1];
        assert($fieldType === 11); // string
        $fieldId = unpack('n', $read(2))[1];
        assert($fieldId === 0);
        $len = unpack('N', $read(4))[1];
        $str = $read($len);
        $fieldType = unpack('c', $read(1))[1];
        assert($fieldType === 0); // stop

        return $str;
    }

    /**
     * @param array $args
     * @return string
     */
    private static function packArgs(array $args = [])
    {
        foreach ($args as $key => $arg) {
            if (is_object($arg) || is_array($arg)) {
                $args[$key] = json_encode($arg, JSON_BIGINT_AS_STRING, 512);
            } else {
                $args[$key] = strval($arg);
            }
        }
        return json_encode($args, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param string $serviceName
     * @param string $methodName
     * @param string $args
     * @return string
     */
    private static function packThrift($serviceName, $methodName, $args, $seq = 0)
    {
        // pack \Com\Youzan\Nova\Framework\Generic\Service\GenericService::invoke
        $payload = "";

        // -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
        $type = self::$t_call; // call
        $ver1 = self::$ver1 | $type;

        $payload .= pack('N', $ver1);
        $payload .= pack('N', strlen("invoke"));
        $payload .= "invoke";
        $payload .= pack('N', $seq);

        // -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
        // {{{ pack args
        $fieldId = 1;
        $fieldType = 12; // struct
        $payload .= pack('c', $fieldType); // byte
        $payload .= pack('n', $fieldId); //u16

        // -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
        // {{{ pack struct \Com\Youzan\Nova\Framework\Generic\Service\GenericRequest
        $fieldId = 1;
        $fieldType = 11; // string
        $payload .= pack('c', $fieldType);
        $payload .= pack('n', $fieldId);
        $payload .= pack('N', strlen($serviceName));
        $payload .= $serviceName;

        $fieldId = 2;
        $fieldType = 11;
        $payload .= pack('c', $fieldType);
        $payload .= pack('n', $fieldId);
        $payload .= pack('N', strlen($methodName));
        $payload .= $methodName;

        $fieldId = 3;
        $fieldType = 11;
        $payload .= pack('c', $fieldType);
        $payload .= pack('n', $fieldId);
        $payload .= pack('N', strlen($args));
        $payload .= $args;

        $payload .= pack('c', 0); // stop
        // pack struct end }}}
        // -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-

        $payload .= pack('c', 0); // stop
        // pack arg end }}}
        // -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-

        return $payload;
    }
}

class file
{
    private $file;
    private $content;
    private $offset;
    private $chunk;
    private $complete;

    public function __construct($file, $chunk = 1024 * 1024)
    {
        $this->file = $file;
        $this->chunk = $chunk;
    }

    public function write($content, $offset, $complete)
    {
        $this->content = $content;
        $this->offset = $offset;
        $this->putContents();
        $this->complete = $complete;
    }

    private function putContents()
    {
        $content = substr($this->content, 0, $this->chunk);
        if ($content === false) {
            call_user_func($this->complete, false);
            return false;
        }

        return swoole_async_write($this->file, $content, $this->offset, function($filename, $size) {
            $this->content = substr($this->content, $size);
            $this->offset += $size;

            if ($this->content !== false && strlen($this->content)) {
                $this->putContents();
            } else {
                call_user_func($this->complete, $this->offset);
                $this->content = "";
                $this->offset = 0;
            }
        });
    }
}

function file_put_contents2($file, $content, $offset = 0, callable $cb)
{
    $file = new file($file);
    $file->write($content, $offset, $cb);
}

function tcp_payload($k)
{
    $payload = str_repeat("o", 1024 * $k - 12);
    $payload = "hello{$payload} world!";
    $len = pack("N", strlen($payload));
    return $len . $payload;
}