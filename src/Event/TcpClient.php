<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/6/14
 * Time: 上午1:53
 */

namespace Minimalism\Event;


class TcpClient
{
    const READ_BUFFER = 8192;

    private $onConnect, $onReceive, $onClose;
    public $ev, $s;
    public $errno, $errstr;

    public function __construct(EventLoop $ev)
    {
        $this->ev = $ev;
    }

    public function connect($remote)
    {
        $r = stream_socket_client($remote, $this->errno, $this->errstr, 0, STREAM_CLIENT_ASYNC_CONNECT);
        if ($r === false) {
            return false;
        }

        stream_set_blocking($r, 0);
        $this->s = $r;
        $this->ev->onWrite($this->s, function(EventLoop $ev, $s) {
            $ev->onWrite($s, null);
            if ($onConnect = $this->onConnect) {
                $onConnect($this, $s);
            }

            $ev->onRead($s, function(EventLoop $ev, $s) {
                $recv = fread($s, static::READ_BUFFER);
                if ($recv === "" || $recv === false) {
                    $this->close();
                } else {
                    $onRecv = $this->onReceive;
                    $onRecv($this, $s, $recv);
                }
            });
        });
    }

    public function close()
    {
        if ($this->s) {
            fclose($this->s);
            $this->ev->onRead($this->s, null);
            $this->ev->onWrite($this->s, null);

            if ($onClose = $this->onClose) {
                $onClose($this, $this->s); // onClose invoke close, 死循环...
            }
        }
    }

    public function isConnected()
    {
        // is connected === is_resource
        // 已经close: resource(n) of type (Unknown)
        return is_resource($this->s);
    }

    public function send($data)
    {
        if ($this->s) {
            $this->ev->onWrite($this->s, function(EventLoop $ev, $s) use(&$data) {
                if ($data === "") {
                    $ev->onWrite($s, null);
                } else {
                    $n = fwrite($this->s, $data);
                    if ($n === false) {
                        $this->close();
                    } else {
                        if ($n === strlen($data)) {
                            $ev->onWrite($s, null);
                        } else {
                            $data = substr($data, $n);
                        }
                    }
                }
            });
            return true;
        } else {
            return false;
        }
    }

    public function on($ev, callable $on)
    {
        switch (strtolower($ev)) {
            case "connect":
                $this->onConnect = $on;
                break;

            case "receive":
                $this->onReceive = $on;
                break;

            case "close":
                $this->onClose = $on;
                break;

            default:
                return false;
        }
        return true;
    }
}