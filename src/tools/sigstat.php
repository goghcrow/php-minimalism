#!/usr/bin/env php

<?php
/**
 * 查看进程与线程的信号信息
 * chuxiaofeng
 * Usage: $argv[0] <PID>\n
 *
 * 一则yz-swoole 多线程与信号bug
 * 打印出来进程与所有线程的信号信息, 发现线程没有block信号, 信号会被随机发送到某个线程,
 * 恰好发送到没有block的信息, SIGTERM信号默认行为退出进程

 * e.g.
 * pidof php | xargs  php $argv[0]
 * ps axu|grep php|grep -v grep | awk '{print $2}'|xargs php $argv[0].php
 */



$usage = "Usage : $argv[0] <pid>\n";
checkEnv($usage);

unset($argv[0]);
if (empty($argv)) {
    $argv[] = "self";
}

foreach ($argv as $pid) {
    if (!is_readable("/proc/$pid/status")) {
        echo "Pid $pid is not exist\n";
        continue;
    }

    echo printSigInfo($pid);
}


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



function printSigInfo($pid)
{
    $outbuf = "";

    // get thread signal info
    $content = file_get_contents("/proc/$pid/status");

    preg_match_all('/^(?<sigName>(Sig|Shd)[a-zA-Z]{3}):\s(?<sigMask>[A-Fa-f0-9]{16})/m', $content, $matches);
    preg_match("/Name:\s*(?<procName>\w+)/", $content, $nameMatch);
    // end

    $outbuf .= "\n\n  --Signal map for {$nameMatch["procName"]} (pid $pid)--\n\n";

    foreach ($matches["sigName"] as $i => $sigName) {
        $sigMask = $matches["sigMask"][$i];
        $outbuf .= "  $sigName: " . fmtSigMask($sigMask) . "\n";
    }

    // 线程信号信息
    $tids = getTids($pid);
    if (count($tids) > 1) {
        foreach ($tids as $tid) {

            // get thread signal into
            $content = file_get_contents("/proc/$pid/task/$tid/status");
            preg_match_all('/^(?<sigName>(Sig|Shd)[a-zA-Z]{3}):\s(?<sigMask>[A-Fa-f0-9]{16})/m', $content, $matches);

            $outbuf .=  "\nTid   :$tid\n";

            foreach ($matches["sigName"] as $i => $sigName) {
                $sigMask = $matches["sigMask"][$i];
                $outbuf .= "$sigName: " . fmtSigMask($sigMask) . "\n";
            }
        }
    }

    return $outbuf;
}

// 获取进程当前的线程id列表
function getTids($pid)
{
    $tids = array_diff(scandir("/proc/$pid/task") ?: [], ["..", "."]);
    return array_map("intval", array_values($tids));
}

// 格式化信号掩码
function fmtSigMask($sigMask)
{
    $signals = [];
    $binStr = base_convert($sigMask, 16, 2);
    foreach (str_split(strrev(strval($binStr))) as $bit => $v) {
        if ($v) {
            $signo = $bit + 1;
            $signals[] = signo2name($signo) . "($signo)";
        }
    }
    return implode(" ", $signals);
}

function fmtSigMask2($sigMask)
{
    $signals = [];
    $hex = 0;
    eval(sprintf('$hex = 0x%s;',$sigMask));
    for ($i = 0; $i < 32; $i++) {
        if ($hex & (1 << $i)) {
            $signo = $i + 1;
            $signals[] = signo2name($i) . "($signo)";
        }
    }
    return implode(" ", $signals);
}

// 信号值转名称
function signo2name($signo)
{
    static $sigList;

    if (!$sigList) {
        $sigList = explode(" ", `kill -l`);
    }
    return isset($sigList[$signo-1]) ? $sigList[$signo-1] : "";
}


/*
kill -l
const SIGMAP = [ 1 => 'SIGHUP', 2 => 'SIGINT', 3 => 'SIGQUIT', 4 => 'SIGILL', 5 => 'SIGTRAP',
                   6 => 'SIGABRT', 7 => 'SIGBUS', 8 => 'SIGFPE', 9 => 'SIGKILL', 10 => 'SIGUSR1',
                  11 => 'SIGSEGV', 12 => 'SIGUSR2', 13 => 'SIGPIPE', 14 => 'SIGALRM', 15 => 'SIGTERM',
                  16 => 'SIGSTKFLT', 17 => 'SIGCHLD', 18 => 'SIGCONT', 19 => 'SIGSTOP', 20 => 'SIGTSTP',
                  21 => 'SIGTTIN', 22 => 'SIGTTOU', 23 => 'SIGURG', 24 => 'SIGXCPU', 25 => 'SIGXFSZ',
                  26 => 'SIGVTALRM', 27 => 'SIGPROF', 28 => 'SIGWINCH', 29 => 'SIGIO', 30 => 'SIGPWR',
                  31 => 'SIGSYS', 32 => 'SIGCANCEL', 33 => 'SIGSETXID', 34 => 'SIGRTMIN', 35 => 'SIGRTMIN+1',
                  // 32 and 33 are glibc signals (https://sourceware.org/git/?p=glibc.git;a=blob;h=fa89cbf44a3e0cd23856d980baa9def8b1cc358d;hb=75f0d3040a2c2de8842bfa7a09e11da1a73e17d0#l307)
                  36 => 'SIGRTMIN+2', 37 => 'SIGRTMIN+3',
                  38 => 'SIGRTMIN+4', 39 => 'SIGRTMIN+5', 40 => 'SIGRTMIN+6', 41 => 'SIGRTMIN+7', 42 => 'SIGRTMIN+8',
                  43 => 'SIGRTMIN+9', 44 => 'SIGRTMIN+10', 45 => 'SIGRTMIN+11', 46 => 'SIGRTMIN+12', 47 => 'SIGRTMIN+13',
                  48 => 'SIGRTMIN+14', 49 => 'SIGRTMIN+15', 50 => 'SIGRTMAX-14', 51 => 'SIGRTMAX-13', 52 => 'SIGRTMAX-12',
                  53 => 'SIGRTMAX-11', 54 => 'SIGRTMAX-10', 55 => 'SIGRTMAX-9', 56 => 'SIGRTMAX-8', 57 => 'SIGRTMAX-7',
                  58 => 'SIGRTMAX-6', 59 => 'SIGRTMAX-5', 60 => 'SIGRTMAX-4', 61 => 'SIGRTMAX-3', 62 => 'SIGRTMAX-2',
                  63 => 'SIGRTMAX-1', 64 => 'SIGRTMAX'];
 */


/*
读取 /proc/$PID/status 信号位掩码(16进制)
最低有效位代表1,左一位代表信号2,以此类推
SigPnd 基于线程的等待信号
ShdPnd 进程级等待信号 >= linux 2.6
SigBlk 阻塞信号
SigIgn 忽略信号
SigCat 捕获信号


linux 内核文档 ./filesystems/proc.txt 中表格

Table 1-2: Contents of the status files (as of 2.6.30-rc7)
..............................................................................
 Field                       Content
 Name                        filename of the executable
 State                       state (R is running, S is sleeping, D is sleeping
                             in an uninterruptible wait, Z is zombie,
			     T is traced or stopped)
 Tgid                        thread group ID
 Pid                         process id
 PPid                        process id of the parent process
 TracerPid                   PID of process tracing this process (0 if not)
 Uid                         Real, effective, saved set, and  file system UIDs
 Gid                         Real, effective, saved set, and  file system GIDs
 FDSize                      number of file descriptor slots currently allocated
 Groups                      supplementary group list
 VmPeak                      peak virtual memory size
 VmSize                      total program size
 VmLck                       locked memory size
 VmHWM                       peak resident set size ("high water mark")
 VmRSS                       size of memory portions
 VmData                      size of data, stack, and text segments
 VmStk                       size of data, stack, and text segments
 VmExe                       size of text segment
 VmLib                       size of shared library code
 VmPTE                       size of page table entries
 VmSwap                      size of swap usage (the number of referred swapents)
 Threads                     number of threads
 SigQ                        number of signals queued/max. number for queue
 SigPnd                      bitmap of pending signals for the thread
 ShdPnd                      bitmap of shared pending signals for the process
 SigBlk                      bitmap of blocked signals
 SigIgn                      bitmap of ignored signals
 SigCgt                      bitmap of catched signals
 CapInh                      bitmap of inheritable capabilities
 CapPrm                      bitmap of permitted capabilities
 CapEff                      bitmap of effective capabilities
 CapBnd                      bitmap of capabilities bounding set
 Seccomp                     seccomp mode, like prctl(PR_GET_SECCOMP, ...)
 Cpus_allowed                mask of CPUs on which this process may run
 Cpus_allowed_list           Same as previous, but in "list format"
 Mems_allowed                mask of memory nodes allowed to this process
 Mems_allowed_list           Same as previous, but in "list format"
 voluntary_ctxt_switches     number of voluntary context switches
 nonvoluntary_ctxt_switches  number of non voluntary context switches
..............................................................................

// from stackoverflow

Under Linux, you can find the PID of your process, then look at /proc/$PID/status.
It contains lines describing which signals are
    blocked (SigBlk), ignored (SigIgn), or caught (SigCgt).

# cat /proc/1/status
...
SigBlk: 0000000000000000
SigIgn: fffffffe57f0d8fc
SigCgt: 00000000280b2603
...
The number to the right is a bitmask.
If you convert it from hex to binary, each 1-bit represents a caught signal,
counting from right to left starting with 1.
So by interpreting the SigCgt line,
we can see that my init process is catching the following signals:

00000000280b2603 ==> 101000000010110010011000000011
                     | |       | ||  |  ||       |`->  1 = SIGHUP
                     | |       | ||  |  ||       `-->  2 = SIGINT
                     | |       | ||  |  |`----------> 10 = SIGUSR1
                     | |       | ||  |  `-----------> 11 = SIGSEGV
                     | |       | ||  `--------------> 14 = SIGALRM
                     | |       | |`-----------------> 17 = SIGCHLD
                     | |       | `------------------> 18 = SIGCONT
                     | |       `--------------------> 20 = SIGTSTP
                     | `----------------------------> 28 = SIGWINCH
                     `------------------------------> 30 = SIGPWR

signo-to-name mapping by running kill -l from bash
*/