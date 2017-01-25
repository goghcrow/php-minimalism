#!/usr/bin/env php
<?php

/**
 * @author xiaofeng
 * 连接池信息查看
 */

$usage = "Usage: $argv[0] ZanProjectRoot\n";
checkEnv($usage);

$runMode = getenv('KDT_RUN_MODE') ?: get_cfg_var('kdt.RUN_MODE') ?: "online";
$connDir = "$argv[1]/resource/config/$runMode/connection";
if (!file_exists($connDir)) {
    exit($usage);
}
statConn($connDir);


function checkEnv($usage)
{
    global $argv;

    if (!ini_get("register_argc_argv")) {
        echo "You must turn 'register_argc_argv' to On in php.ini\n";
        exit(1);
    }

    // assert(PHP_OS === "Linux", "Only Support Linux");
    if (!is_readable("/proc/1/status")) {
        echo "/proc/1/status does not exist, what OS are you running ?\n";
        exit(1);
    }

    if (isset($argv[1]) && $argv[1] === "--help") {
        echo $usage;
        exit(1);
    }
}

/**
 * 读取zan项目connection配置
 * @param $connDir
 */
function statConn($connDir) {
    $stat = [];

    $iter = new \RecursiveDirectoryIterator($connDir, \RecursiveDirectoryIterator::SKIP_DOTS);
    $iter = new \RecursiveIteratorIterator($iter, \RecursiveIteratorIterator::LEAVES_ONLY);
    foreach ($iter as $fileInfo) {

        /* @var $fileInfo \SplFileInfo */
        $ext = $fileInfo->getExtension();
        $connName = substr($fileInfo->getFilename(), 0, -strlen($ext) - 1);

        /** @noinspection PhpIncludeInspection */
        $connInfo = require_once $fileInfo->getRealPath();

        if (!is_array($connInfo)) {
            continue;
        }

        $connStat = [];
        foreach ($connInfo as $connKey => $conn) {
            if (isset($conn["pool"]) && $conn["pool"]) {
                list($host, $port) = [$conn["host"], $conn["port"]];
                $connStat["$connKey#$host:$port"] = TcpStat::count($host, $port);
            }
        }

        if ($connStat) {
            $stat[$connName] = $connStat;
        }
    }

    echo json_encode($stat, JSON_PRETTY_PRINT), PHP_EOL;
}


/**
 * Class TcpStat
 * @package Zan\Framework\Components\ZanBox
 */
class TcpStat
{
    const SS_NETSTAT_TCP_STATE_MAP = [
        "established"   => "ESTABLISHED",
        "syn-sent"      => "SYN_SENT",
        "syn-recv"      => "SYN_RCVD",
        "fin-wait-1"    => "FIN_WAIT_1",
        "fin-wait-2"    => "FIN_WAIT_2",
        "time-wait"     => "TIME_WAIT",
        "closed"        => "CLOSED",
        "close-wait"    => "CLOSE_WAIT",
        "last-ack"      => "LAST_ACK",
        "listen"        => "LISTEN",
        "closing"       => "CLOSING",
    ];

    /**
     * 统计不同状态连接数量, 兼容mac与linux
     * @param string $host
     * @param int $port
     * @param array $states
     * @return array
     */
    public static function count($host, $port, $states = ["established", "time-wait", "close-wait"]) {
        if (!ip2long($host)) {
            $host = gethostbyname($host);
        }

        $pipe = "wc -l";
        $func = PHP_OS === "Darwin" ?  "netstat" : "ss";
        $states = static::fmtTcpState($states, $func);

        $info = [];
        foreach ($states as $state) {
            $ret = call_user_func([static::class, $func], $host, $port, $state, $pipe);
            $info[$state] = intval(trim($ret)) - 1;
        }

        return $info;
    }

    /**
     * @param array $states
     * @param string $type
     * @return array
     */
    public static function fmtTcpState(array $states, $type)
    {
        $from = $to = [];
        if ($type === "ss") {
            $to = static::SS_NETSTAT_TCP_STATE_MAP;
            $from = array_flip($to);
        } else if ($type === "netstat") {
            $from = static::SS_NETSTAT_TCP_STATE_MAP;
            $to = array_flip($from);
        }

        $ret = [];
        foreach ($states as $state) {
            if (isset($to[$state])) {
                $ret[] = $state;
            } else if (isset($from[$state])) {
                $ret[] = $from[$state];
            }
        }
        return $ret;
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private static function netstat($host, $port, $state, $pipe = "")
    {
        if ($pipe) {
            $pipe = " | $pipe";
        }

        // $4 src $5 dst $6 stats
        return `netstat -an | awk '(\$5 == "$host.$port" && \$6 == "$state") || NR==2  {print \$0}' $pipe`;
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private static function ss($host, $port, $state, $pipe = "")
    {
        if ($pipe) {
            $pipe = " | $pipe";
        }

        // http://man7.org/linux/man-pages/man8/ss.8.html
        /**
        Available identifiers are:

        All standard TCP states: established, syn-sent, syn-recv, fin-wait-1, fin-wait-2, time-wait, closed, close-wait, last-ack,listen and closing.

        all - for all the states
        connected - all the states except for listen and closed
        synchronized - all the connected states except for syn-sent
        bucket - states, which are maintained as minisockets, i.e. time-wait and syn-recv
        big - opposite to bucket
         */
        return `ss state $state dst $host:$port $pipe`;
    }
}
