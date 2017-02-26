<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/7
 * Time: 下午8:13
 */

namespace Minimalism\A\Client;
use Minimalism\A\Client\Exception\AsyncTcpClientException;


/**
 * Class AsyncTcpClient
 * @package Minimalism\A
 *
 * @method bool isConnected()
 * @method resource getSocket()
 * @method bool close()
 */
class AsyncTcpClient extends AsyncWithTimeout
{
    /** @var callable */
    public $k;
    public $client;
    public $ip;
    public $port;
    public $data;

    /**
     * AsyncTcpClient constructor.
     * @param array $set
     * 
     * e.g. 
     * $set = [
     * 'open_eof_check' => true,
     * 'package_eof' => "\r\n\r\n",
     * "socket_buffer_size" => 1024 * 1024 * 8,
     * ]
     */
    public function __construct(array $set = [])
    {
        $this->client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);

        $this->client->set($set);

        $this->client->on("connect", [$this, "onConnect"]);
        $this->client->on("close", [$this, "onClose"]);
        $this->client->on("error", [$this, "onError"]);
        $this->client->on("receive", [$this, "onReceive"]);

        $this->k = [$this, "doConnect"];
    }

    public function __get($name)
    {
        return $this->client->$name;
    }

    public function __call($name, $arguments)
    {
        $m = $this->client->$name;
        return $m(...$arguments);
    }

    public function connect($ip, $port, $timeout = 1000)
    {
        $this->ip = $ip;
        $this->port = $port;
        $this->timeout = $timeout;
        $this->k = function() {
            $r = $this->client->connect($this->ip, $this->port);
            if (!$r) {
                $this->ithrow("connect fail");
            }
        };
        return $this;
    }

    /**
     * @param string $data
     * @param int $timeout 接收数据timeout
     * @return $this
     */
    public function send($data, $timeout = 1000)
    {
        $this->data = $data;
        $this->timeout = $timeout;
        $this->k = function() {
            $len = $this->client->send($this->data);
            if (!$len) {
                $this->ithrow("send fail");
            }
        };
        return $this;
    }

    // ⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇internal⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇

    /**
     * @param \swoole_client $client
     * @internal
     */
    public function onConnect(\swoole_client $client)
    {
        if ($client->errCode) {
            $this->ithrow("on connect error");
        } else {
            $this->returnVal($client);
        }
    }

    /**
     * @param \swoole_client $client
     * @param $data
     * @internal
     */
    public function onReceive(\swoole_client $client, $data)
    {
        if ($client->errCode) {
            $this->ithrow("on receive error");
        } else {
            $this->returnVal($data);
        }
    }

    /**
     * @internal
     */
    public function onError(/*\swoole_client $client*/)
    {
        $this->ithrow("error");
    }

    /**
     * @internal
     */
    public function onClose(/*\swoole_client $client*/)
    {
        $this->ithrow("close");
    }

    /**
     * @internal
     */
    protected function execute()
    {
        $k = $this->k;
        $k();
    }

    /**
     * @param $msg
     * @internal
     */
    private function ithrow($msg)
    {
        $this->throwEx(new AsyncTcpClientException($msg, $this->client->errCode));
    }
}