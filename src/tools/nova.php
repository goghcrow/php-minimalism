#!/usr/bin/env php
<?php

if (isset($argv[1]) && $argv[1] === "install") {
    $self = __FILE__;
    `rm -rf /usr/local/bin/nova`;
    `chmod +x $self && cp $self /usr/local/bin/nova`;
    exit();
}

$usage = <<<USAGE
Usage: nova -h主机 -p端口 -m方法 -a参数
    nova -hqabb-dev-scrm-test0 -p8100 -mcom.youzan.scrm.customer.service.customerService.getByYzUid -a '{"kdtId":1, "yzUid": 1}'
    nova -h10.9.97.143 -p8050 -m=com.youzan.material.general.service.TokenService.getToken -a='{"kdtId":1,"scope":""}'
    nova -h10.9.188.33 -p8050 -m=com.youzan.material.general.service.MediaService.getMediaList -a='{"query":{"categoryId":2,"kdtId":1,"pageNo":1,"pageSize":5}}'
USAGE;

$a = getopt("h:p:m:a:");
if (!isset($a['h']) || !isset($a['p']) || !isset($a['m']) || !isset($a['a'])) {
    echo "\033[1m$usage\033[0m\n";
    exit(1);
}

$args = json_decode($a['a'], true);
if ($args === null) {
    $args = [];
}
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "\033[1;31m", "JSON参数有误: ", json_last_error_msg(), "\033[0m\n";
    exit(1);
}

$host = $a['h'];
$port = $a['p'];

$service_method = $a['m'];
$split = strrpos($service_method, ".");
if ($split === false) {
    echo "\033[1;31m", "方法错误: $service_method", "\033[0m\n";
    exit(1);
}
$service = substr($service_method, 0, $split);
$method = substr($service_method, $split + 1);

$attach = [];

$nova = new NovaService($host, $port);
$nova->invoke($service, $method, $args, [], function(\swoole_client $cli, $res, $err, $attach) use ($nova, $service, $method, $args) {
    if ($err) {
        echo "\033[1;31m", json_encode($err, JSON_PRETTY_PRINT), "\033[0m\n";
    } else {
        echo "\033[1;32m", json_encode($res, JSON_PRETTY_PRINT), "\033[0m\n";
    }
    $cli->close();
    swoole_event_exit();
});



class NovaService
{
    private static $auto_reconnect = false;

    private static $ver_mask = 0xffff0000;
    private static $ver1 = 0x80010000;

    private static $t_call  = 1;
    private static $t_reply  = 2;
    private static $t_ex  = 3;

    /** @var \swoole_client */
    public $client;
    private $host;
    private $port;
    private $recvArgs;

    public function __construct($host, $port, $timeout = 2000)
    {
        $this->host = $host;
        $this->port = $port;

        $this->client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
        $this->init();
    }

    public function invoke($service, $method, array $args, array $attach, callable $onReceive)
    {
        $this->recvArgs = func_get_args();

        if ($this->client->isConnected()) {
            $this->send();
        } else {
            $this->connect();
        }
    }

    private function init()
    {
        $this->client->set([
            "open_length_check" => 1,
            "package_length_type" => 'N',
            "package_length_offset" => 0,
            "package_body_offset" => 0,
            "open_nova_protocol" => 1,
            "socket_buffer_size" => 1024 * 1024 * 2,
        ]);

        $this->client->on("error", function(\swoole_client $client) {
            $this->cancel("connect_timeout");
            echo "\033[1;31m", "连接出错: ", socket_strerror($client->errCode), "\033[0m\n";
            if (self::$auto_reconnect) {
                $this->connect();
            } else {
                swoole_event_exit();
                exit(1);
            }
        });

        $this->client->on("close", function(\swoole_client $client) {
            // echo "close\n";
            $this->cancel("connect_timeout");
        });
        $this->client->on("receive", function(\swoole_client $client, $data) {
            // fwrite(STDERR, "recv: " . implode(" ", str_split(bin2hex($data), 2)) . "\n");
            $recv = end($this->recvArgs);
            $recv($client, ...self::unpackResponse($data));
        });
    }

    private function connect()
    {
        $this->client->on("connect", function(\swoole_client $client) {
            $this->cancel("connect_timeout");
            $this->invoke(...$this->recvArgs);
        });

        $this->deadline(2000, "connect_timeout");

        DnsClient::lookup($this->host, function($host, $ip) {
            if ($ip === null) {
                echo "\033[1;31m", "DNS查询超时 host:{$host}", "\033[0m\n";exit;
            } else {
                $this->client->connect($ip, $this->port);
            }
        });
    }

    private function send()
    {
        $novaBin = self::packNova(...$this->recvArgs); // 多一个onRecv参数,不过没关系
        assert($this->client->send($novaBin));
    }

    /**
     * @param string $recv
     * @return array
     */
    private static function unpackResponse($recv)
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
     * @param string $service
     * @param string $method
     * @param array $args
     * @param array $attach
     * @return string
     */
    private function packNova($service, $method, array $args, array $attach)
    {
        $args = self::packArgs($args);
        $thriftBin = self::packThrift($service, $method, $args);
        $attach = json_encode($attach, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $sockInfo = $this->client->getsockname();
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

    /**
     * @param int $duration
     * @param string $prop
     * @return bool|int
     */
    private function deadline($duration, $prop)
    {
        if (property_exists($this->client, $prop)) {
            return false;
        }
        return $this->client->{$prop} = swoole_timer_after($duration, function() {
            echo "\033[1;31m", "连接超时", "\033[0m\n";
            $this->client->close();
        });
    }

    /**
     * @param $prop
     * @return bool
     */
    private function cancel($prop)
    {
        if (property_exists($this->client, $prop)) {
            $s = swoole_timer_clear($this->client->{$prop});
            unset($this->client->{$prop});
            return $s;
        }
        return false;
    }
}



class DnsClient
{
    const maxRetryCount = 3;
    const timeout = 100;
    private $timerId;
    public $count = 0;
    public $host;
    public $callback;

    public static function lookup($host, $callback)
    {
        $self = new static;
        $self->host = $host;
        $self->callback = $callback;
        return $self->resolve();
    }

    public function resolve()
    {
        $this->onTimeout(static::timeout);

        return swoole_async_dns_lookup($this->host, function($host, $ip) {
            if ($this->timerId && swoole_timer_exists($this->timerId)) {
                swoole_timer_clear($this->timerId);
            }
            call_user_func($this->callback, $host, $ip);
        });
    }


    public function onTimeout($duration)
    {
        if ($this->count < static::maxRetryCount) {
            $this->timerId = swoole_timer_after($duration, [$this, "resolve"]);
            $this->count++;
        } else {
            $this->timerId = swoole_timer_after($duration, function() {
                call_user_func($this->callback, $this->host, null);
            });
        }
    }
}