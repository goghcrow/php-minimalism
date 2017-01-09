<?php

/**
 * @author xiaofeng
 * blocked fd 查询
 */

/*
lsof -p $pid
/usr/include/bits/fcntl.h
#define O_NONBLOCK	  04000
*/

defined("O_NONBLOCK") or define("O_NONBLOCK", 04000);


$usage = "Usage: $argv[0] pid\n";
checkEnv($usage);

printBlockFd($argv[1]);


function checkEnv($usage)
{
    global $argv;

    if (!ini_get("register_argc_argv")) {
        echo "You must turn 'register_argc_argv' to On in php.ini\n";
        exit(1);
    }

    if (!isset($argv[1]) || $argv[1] === "--help" || $argv[1] === "-h") {
        echo $usage;
        exit(1);
    }

    if (!is_readable("/proc/$argv[1]/status")) {
        echo "/proc/$argv[1]/status does not exist\n";
        exit(1);
    }
}


function printBlockFd($pid = "self")
{
    if ($pid && !is_readable("/proc/$pid")) {
        exit("Pid $pid is not exist");
    }

    $tids = array_diff(scandir("/proc/$pid/fdinfo") ?: [], ["..", "."]);
    $fds = array_map("intval", array_values($tids));

    foreach ($fds as $fd) {
        $fdInfo = file_get_contents("/proc/$pid/fdinfo/$fd");
        preg_match("#flags:\s(\d*)\n#", $fdInfo, $ret);
        $flags = $ret[1];
        if (!(intval($flags, 8) & O_NONBLOCK)) {
            echo "O_NONBLOCK [fd=$fd, flags=$flags]\n";
        }
    }
}