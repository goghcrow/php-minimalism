<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/16
 * Time: 下午10:26
 */

namespace Minimalism\Benchmark;


class TcpClient extends Client
{
    /**
     * @var \swoole_client
     */
    public $client;

    public function connect()
    {
        $this->client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
        $this->client->set($this->setting);
        $this->client->on("connect", [$this, "onConnect"]);
        $this->client->on("close", [$this, "onClose"]);
        $this->client->on("error", [$this, "onError"]);
        $this->client->on("receive", [$this, "onReceive"]);
        $this->addTimer($this->conf->connTimeout, [$this, "onTimeout"]);

        return $this->client->connect($this->conf->ip, $this->conf->port);
    }

    public function request()
    {
        return $this->client->send($this->send);
    }

    public function errno()
    {
        return $this->client->errCode;
    }

    public function error()
    {
        return socket_strerror($this->client->errCode);
    }
}