<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/6/16
 * Time: 上午1:48
 */

namespace Minimalism\Event;


class Process
{
    /** @var EventLoop */
    private $ev;

    private $redirectUnix;

    private $runnable, $onReadSTDOUT, $onReadSTDERR;

    private $sockServer, $sockSTDOUT, $sockSTDERR;

    public function __construct(EventLoop $ev,
                                callable $runnable,
                                callable $onReadSTDOUT = null,
                                callable $onReadSTDERR = null)
    {
        $this->ev = $ev;
        $this->runnable = $runnable;

        $this->onReadSTDOUT = $onReadSTDOUT;
        $this->onReadSTDERR = $onReadSTDERR;

        $this->initUnixSocket();
    }

    private function initUnixSocket()
    {
        $tmp = sys_get_temp_dir();
        $pid = getmypid();
        $now = time();
        $this->redirectUnix = "unix:///$tmp/process_{$now}_{$pid}.sock";

        register_shutdown_function(function() {
            @unlink($this->redirectUnix);
        });
    }

    private function sockClear()
    {
        if (is_resource($this->sockSTDOUT)) {
            fclose($this->sockSTDOUT);
        }
        if (is_resource($this->sockSTDERR)) {
            fclose($this->sockSTDERR);
        }
        if (is_resource($this->sockServer)) {
            fclose($this->sockServer);
        }
    }

    private function onRead($pid, $on)
    {
        return function(EventLoop $ev, $s) use($pid, $on) {
            $recv = "";
            if (is_resource($s)) {
                // $recv = fread($s, 8192);
                $recv = stream_get_contents($s);
            }

            if ($recv === "" || $recv === false) {
                // close
                $this->ev->onRead($this->sockSTDOUT, null);
                $this->ev->onRead($this->sockSTDERR, null);
                $this->sockClear();
                pcntl_waitpid($pid, $status); // block
            } else {
                if (is_callable($on)) {
                    $on($recv);
                } else {
                    fprintf(STDERR, $recv);
                }
            }
        };
    }

    public function exec($path, $args = [], $envs = [])
    {
        pcntl_exec($path, $args, $envs);
    }

    public function run()
    {
        list($sock0, $sock1) = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

        $pid = pcntl_fork();

        if ($pid < 0) {
            throw new \RuntimeException("fork fail");
        }

        if ($pid === 0) {
            fclose($sock1);

            /*
            可以简单的通过ob_start重定向，STDOUT通过ob转到父进程管道, STDERR不变, 处理不了pcntl_exec
            ob_start(function($buffer) {
                if (is_resource($this->sockSTDOUT)) {
                    fwrite($this->sockSTDOUT, $buffer);
                } else {
                    fprintf(STDERR, "Broken Pipe\n");
                    exit(255);
                }
            });
            */

            $op = fgets($sock0); // 阻塞等待父进程unix socket server创建好
            if ($op === "KO") {
                exit(255);
            }
            fclose($sock0);

            // 到父进程unix socket 替代 STDOUT
            fclose(STDOUT);
            $this->sockSTDOUT = stream_socket_client($this->redirectUnix, $errno, $errstr);
            if (is_resource($this->sockSTDOUT) === false) {
                fprintf(STDERR, "$errstr");
                exit(255);
            }

            fclose(STDERR);
            $this->sockSTDERR = stream_socket_client($this->redirectUnix, $errno, $errstr, 1); // 1s timeout
            if (is_resource($this->sockSTDERR) === false) {
                exit(255);
            }

            $fn = $this->runnable;
            $r = $fn($this);
            exit($r);

        } else if ($pid > 0) {
            fclose($sock0);

            $this->sockServer = stream_socket_server($this->redirectUnix, $errno, $errstr);
            if (is_resource($this->sockServer)) {
                fwrite($sock1, "OK\n");
                fclose($sock1);
            } else {
                fwrite($sock1, "KO\n");
                fclose($sock1);
                throw new \RuntimeException("stream_socket_server fail");
            }

            $this->sockSTDOUT = stream_socket_accept($this->sockServer, 1); // 1s timeout
            $this->sockSTDERR = stream_socket_accept($this->sockServer, 1); // 1s timeout

            if (!is_resource($this->sockSTDOUT) || !is_resource($this->sockSTDERR)) {
                $this->sockClear();
                pcntl_waitpid($pid, $status);
                throw new \RuntimeException("stream_socket_accept fail");
            }

            stream_set_blocking($this->sockSTDOUT, 0);
            stream_set_blocking($this->sockSTDERR, 0);
            $this->ev->onRead($this->sockSTDOUT, $this->onRead($pid, $this->onReadSTDOUT));
            $this->ev->onRead($this->sockSTDERR, $this->onRead($pid, $this->onReadSTDERR));
        }

        return $pid;
    }
}