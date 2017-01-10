<?php

namespace Minimalism;

/**
 * Class Proc
 * @package Minimalism
 *
 * @property $command   string      The command string that was passed to proc_open().
 * @property $pid       int         process id
 * @property $running   bool        TRUE if the process is still running, FALSE if it has terminated.
 * @property $signaled  bool        TRUE if the child process has been terminated by an uncaught signal.
 * @property $stopped   bool        TRUE if the child process has been stopped by a signal.
 * @property $exitcode  int         The exit code returned by the process (which is only meaningful if running is FALSE). Only first call of this function return real value, next calls return -1.
 * @property $termsig   int         The number of the signal that caused the child process to terminate its execution (only meaningful if signaled is TRUE).
 * @property $stopsig   int         The number of the signal that caused the child process to stop its execution (only meaningful if stopped is TRUE).
 *
 */
class Process
{
    /**
     * @var null|string
     */
    private $cmd;

    /**
     * @var null|array|string
     */
    private $args;

    /**
     * @var null|string
     */
    private $input;

    /**
     * @var null|string change work dir 绝对路径
     */
    private $cwd;

    /**
     * @var null|array 环境变量
     */
    private $env;

    /**
     * @var array
     */
    private $pipes;

    /**
     * @var resource
     */
    private $proc;

    /**
     * @var array
     */
    private $stat;

    /**
     * Process constructor.
     * 一定要把cmd 和 args 分开传, 否则因为转义会导致执行错误
     * @param string $cmd
     * @param array|string $args
     * @param string|null $input 会被写到 子进程标准输入
     */
    public function __construct($cmd, $args = null, $input = null)
    {
        $this->cmd = $cmd;
        $this->args = $args;
        $this->input = $input;
    }

    /**
     * @param string $cmd
     * @param null|array $args
     * @param null|string $input
     * @param null|int $tv_sec
     * @param null|int $tv_usec
     * @param null|string $cwd
     * @param array $env
     * @return array [out, err]
     */
    public static function exec($cmd, $args = null, $input = null, $tv_sec = null, $tv_usec = null, $cwd = null, array $env = null)
    {
        $self = new static($cmd, $args, $input);
        if ($cwd) $self->setCwd($cwd);
        if ($env) $self->setEnv($env);

        if ($self->start()) {
            if ($self->getResult($out, $err, $tv_sec, $tv_usec) === false) {
                throw new ProcessException("execute timeout", 2);
            }
        } else {
            throw new ProcessException("start error", 1);
        }

        return [$out, $err];
    }

    /**
     * 开始执行
     * @return bool
     */
    public function start()
    {
        $cmd = $this->sanitizeCmdline($this->cmd, $this->args);

        $this->proc = proc_open($cmd, [
            0 => ["pipe", "r"],
            1 => ["pipe", "w"],
            2 => ["pipe", "w"],
        ], $this->pipes, $this->cwd, $this->env, ['suppress_errors' => true, 'binary_pipes' => true/* for win*/]);

        if ($this->proc === false) {
            return false;
        }

        if ($this->input !== null) {
            $n = fwrite($this->pipes[0], $this->input);
            if (strlen($this->input) !== $n) {
                $this->closePipes();
                return false;
            }
        }

        // 这里必须close
        assert(fclose($this->pipes[0]));
        unset($this->pipes[0]);

        $this->stat = null;
        return true;
    }

    /**
     * 获取结果
     * @param &string $out
     * @param &string $err
     * @param int|null $tv_sec null 表示无超时, 执行超时会被kill -9
     * @param null $tv_usec
     * @return Process|false 超时返回false
     */
    public function getResult(&$out, &$err, $tv_sec = null, $tv_usec = null)
    {
        // 防止select立即返回, 消耗cpu
        assert(!($tv_sec === 0 && $tv_usec === 0));

        while (true) {
            $r = $this->pipes;
            $w = null;
            $e = null;

            /* 隐藏被信号或者其他系统调用打断 产生的错误*/
            set_error_handler(function() {});
            $n = @stream_select($r, $w, $e, $tv_sec, $tv_usec);
            restore_error_handler();


            if ($n === false) {
                break;
            } else if ($n === 0) {
                assert($this->kill(SIGKILL)); // 超时kill -9
                return false;

            } else if ($n > 0) {
                foreach ($r as $handle) {
                    if ($handle === $this->pipes[1]) {
                        $_ = &$out;
                    } else if ($handle === $this->pipes[2]) {
                        $_ = &$err;
                    } else {
                        $_ = "";
                    }

                    $line = fread($handle, 8192);
                    $isEOF = $line === "";
                    if ($isEOF) {
                        break 2;
                    } else {
                        $_ .= $line;
                    }
                }
            }
        }

        $this->closePipes();

        $this->stat = null;
        return true;
    }

    public function __get($name)
    {
        if (!is_resource($this->proc)) {
            return null;
        }

        if ($this->stat === null) {
            $this->stat = proc_get_status($this->proc);
        }

        if ($name === "stat") {
            return $this->stat;
        }

        if (isset($this->stat[$name])) {
            return $this->stat[$name];
        }
        return null;
    }

    public function kill($signal = SIGTERM)
    {
        $this->stat = null;
        if (is_resource($this->proc)) {
            return proc_terminate($this->proc, $signal);
        } else {
            return false;
        }
    }

    public function setCwd($cwd)
    {
        $this->cwd = realpath($cwd); /* 工作目录 绝对地址*/
    }

    public function setEnv(array $env)
    {
        $this->env = $env;
    }

    public function isRunning()
    {
        return $this->running;
    }

    public function getExitCode()
    {
        return $this->exitcode;
    }

    public function getTermSig()
    {
        if ($this->signaled) {
            return $this->termsig;
        }

        if ($this->stopped) {
            return $this->stopsig;
        }

        $exitCodec = $this->exitcode;
        if ($exitCodec > 128 && $exitCodec < 160) {
            return $exitCodec - 128;
        }

        return false;
    }

    private function sanitizeCmdline($cmd, $args = null)
    {
        $cmd = escapeshellcmd($cmd);
        if (is_array($args)) {
            $args = implode(" ", $args);
        }
        // $args = escapeshellarg($args);
        return "$cmd $args";
    }

    private function closePipes()
    {
        foreach ($this->pipes as $fd => $pipe) {
            if (is_resource($pipe)) {
                assert(fclose($pipe));
            }
            unset($this->pipes[$fd]);
        }

        // 没什么用 !!!
        // if (is_resource($this->proc)) {
            // proc_close
            // Returns the termination status of the process that was run. In case of an error then -1 is returned.
            // NOTICE !!!
            // If PHP has been compiled with --enable-sigchild,
            // the return value of this function is undefined.
            // $terminateCode = proc_close($this->proc); // block
            // $this->proc = null;
        // }
    }

    public function __destruct()
    {
        $this->closePipes();
    }
}


/**
 * spawn_exec
 * @param null|string $cmd command
 * @param null|string $input code
 * @param null|int $tv_sec timeout sec
 * @param null|int $tv_usec timeout usec
 * @param null|string $cwd change work dir
 * @param array|null $env env
 * @return array [out, err]
 */
function spawn_exec($cmd, $input = null, $tv_sec = null, $tv_usec = null, $cwd = null, array $env = null)
{
    $out = $err = null;
    $winOpt = ['suppress_errors' => true, 'binary_pipes' => true];
    $proc = proc_open($cmd, [
        0 => ["pipe", "r"],
        1 => ["pipe", "w"],
        2 => ["pipe", "w"],
    ], $pipes, $cwd, $env, $winOpt);
    assert($proc !== false);

    if ($input !== null) {
        $n = fwrite($pipes[0], $input);
        if (strlen($input) !== $n) {
            goto closePipes;
        }
    }

    // 必须关闭
    assert(fclose($pipes[0]));
    unset($pipes[0]);

    // 防止select立即返回, 消耗cpu
    assert(!($tv_sec === 0 && $tv_usec === 0));

    while (true) {
        $r = $pipes;
        $w = null;
        $e = null;

        /* 隐藏被信号或者其他系统调用打断 产生的错误*/
        set_error_handler(function() {});
        $n = @stream_select($r, $w, $e, $tv_sec, $tv_usec);
        restore_error_handler();

        if ($n === false) {
            break;
        } else if ($n === 0) {
            // 超时kill -9
            assert(proc_terminate($proc, SIGKILL));
            throw new \RuntimeException("exec $cmd time out");

        } else if ($n > 0) {
            foreach ($r as $handle) {
                if ($handle === $pipes[1]) {
                    $_ = &$out;
                } else if ($handle === $pipes[2]) {
                    $_ = &$err;
                } else {
                    $_ = "";
                }

                $line = fread($handle, 8192);
                $isEOF = $line === "";
                if ($isEOF) {
                    break 2;
                } else {
                    $_ .= $line;
                }
            }
        }
    }

    closePipes:
    foreach ($pipes as $fd => $pipe) {
        if (is_resource($pipe)) {
            @fclose($pipe);
        }
        unset($pipes[$fd]);
    }

    return [$out, $err];
}

class ProcessException extends \RuntimeException { }