<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/16
 * Time: 下午10:27
 */

namespace Minimalism\Benchmark;


class MysqlClient extends Client
{
    /**
     * @var \swoole_mysql
     */
    public $client;

    /**
     * @var MysqlTestPlan
     */
    public $test;

    public function connect()
    {
        $this->client = new \swoole_mysql();
        $this->client->on("connect", [$this, "onConnect"]);
        $this->client->on("close", [$this, "onClose"]);
        $this->client->on("error", [$this, "onError"]);

        $this->addTimer($this->conf->connTimeout, [$this, "onTimeout"]);

        return $this->client->connect([
            "ip" => $this->conf->ip,
            "port" => $this->conf->port,
            "user" => $this->test->user,
            "password" => $this->test->password,
            "database" => $this->test->database,
            "charset" => $this->test->charset,
        ]);
    }

    public function request()
    {
        return $this->client->query($this->send, [], [$this, "onReceive"]);
    }

    public function errno()
    {
        return $this->client->errno;
    }

    public function error()
    {
        return $this->client->error;
    }
}