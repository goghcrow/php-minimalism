<?php

namespace Minimalism\functions;

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