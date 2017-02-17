<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/16
 * Time: 下午10:27
 */

namespace Minimalism\Benchmark;


abstract class Client
{
    /**
     * @var \swoole_client|\swoole_http_client|\swoole_mysql
     */
    public $client;

    /**
     * @var TestPlan
     */
    public $test;

    public $lastTs;

    public $timer;

    public $send;

    public $recv;

    public $conf;

    public $setting;

    public $enable;

    public $defaultSetting = [];

    public static function make(TestPlan $test, Config $conf, array $setting = [])
    {
        switch (true) {
            case ($test instanceof TcpTestPlan):
                return new TcpClient($test, $conf, $setting);
            case ($test instanceof HttpTestPlan):
                return new HttpClient($test, $conf, $setting);
            case ($test instanceof NovaTestPlan):
                return new NovaClient($test, $conf, $setting);
            case ($test instanceof MysqlTestPlan):
                return new MysqlClient($test, $conf, $setting);
            default:
                return null;
        }
    }

    public function __construct(TestPlan $test, Config $conf, array $setting = [])
    {
        $this->test = $test;
        $this->conf = $conf;
        $this->setting = $setting + $this->defaultSetting;
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

        if (!$this->connect()) {
            $this->onError();
        }
    }

    public function stop()
    {
        $this->client->close();
        $this->enable = false;
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

    public function onTimeout()
    {
        $this->tickRequest();
        $this->client->close();
        $this->reconnect();
    }

    /**
     * @param $client
     */
    public function onConnect($client)
    {
        $this->cancelTimer();

        if ($this->errno()) {
            $this->onError();
        } else {
            $this->send();
        }
    }

    public function onError()
    {
        $errno = $this->errno();
        $error = $this->error();
        fprintf(STDERR, "error: [errno=$errno, errno=$error]\n");
        $this->reconnect();
    }

    public function onClose()
    {
        $errno = $this->errno();
        $error = $this->error();
        fprintf(STDERR, "close: [errno=$errno, errno=$error]\n");
        $this->reconnect();
    }

    public function tickRequest()
    {
        pcntl_signal_dispatch();

        if ($this->conf->requests !== null && --$this->conf->requests <= 0) {
            $this->stop();
            posix_kill(posix_getppid(), SIGINT);
        }
    }

    public function success()
    {
        $elapsed = Report::now() - $this->lastTs;
        $sentBytes = is_string($this->send) ? strlen($this->send) : 0;
        $bytes = is_string($this->recv) ? strlen($this->recv) : 0;
        Report::success($elapsed, $bytes, $sentBytes);
    }

    public function fail()
    {
        $elapsed = Report::now() - $this->lastTs;
        $sentBytes = is_string($this->send) ? strlen($this->send) : 0;
        $bytes = is_string($this->recv) ? strlen($this->recv) : 0;
        $msg = str_replace(",", ";", $this->error());
        Report::fail($elapsed, $bytes, $sentBytes, $msg, $this->errno());
    }

    public function send()
    {
        $this->send = $this->test->payload($this->client);

        $this->lastTs = Report::now();
        $this->addTimer($this->conf->recvTimeout, [$this, "onTimeout"]);

        if ($this->request()) {
            pcntl_signal_dispatch();
        } else {
            $this->onError();
        }
    }

    public function onReceive($client, $recv = null)
    {
        $this->cancelTimer();
        $this->tickRequest();

        if ($this->errno()) {
            $this->onError();
        } else {
            $this->recv = $recv;

            if ($this->test->assert($client, $recv)) {
                $this->success();
            } else {
                $this->fail();
            }

            $this->send();
        }
    }

    /**
     * @return bool
     */
    abstract public function connect();

    /**
     * @return bool
     */
    abstract public function request();

    /**
     * @return int
     */
    abstract public function errno();

    /**
     * @return string
     */
    abstract public function error();
}