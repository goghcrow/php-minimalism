<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/16
 * Time: 下午10:26
 */

namespace Minimalism\Benchmark;


class HttpClient extends Client
{
    /**
     * @var \swoole_http_client
     */
    public $client;

    /**
     * @var HttpRequest
     */
    public $send;

    /**
     * @param \swoole_http_client  $client
     */
    public function onConnect($client)
    {
        if ($client->errCode) {
            $this->onError();
        }
    }

    public function connect()
    {
        $this->client = new \swoole_http_client($this->conf->ip, $this->conf->port);
        $this->client->set($this->setting);
        $this->client->on("connect", [$this, "onConnect"]);
        $this->client->on("close", [$this, "onClose"]);
        $this->client->on("error", [$this, "onError"]);

        $this->send();
        return true;
    }

    public function request()
    {
        $this->client->setMethod($this->send->method);
        if (!empty($this->send->headers)) {
            // TODO coredump
            // $this->client->setHeaders($this->send->headers);
        }
        if (!empty($this->send->cookies)) {
            // TODO zval problem
            // $this->client->setCookies($this->send->cookies);
        }
        $this->client->setData($this->send->body);

        return $this->client->execute($this->send->uri, [$this, "onReceive"]);
    }

    public function onReceive($client, $body = null)
    {
        $this->cancelTimer();
        $this->tickRequest();

        if ($this->errno()) {
            $this->onError();
        } else {
            $this->recv = $client->body;

            if ($this->test->assert($client, $client->body)) {
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

    public function fail()
    {
        $elapsed = Report::now() - $this->lastTs;
        $sentBytes = strlen($this->send->body);
        $bytes = strlen($this->recv);
        $msg = str_replace(",", ";", $this->errno());
        $code = isset($this->client->statusCode) ? $this->client->statusCode : 0;
        Report::fail($elapsed, $bytes, $sentBytes, $msg, $code);
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