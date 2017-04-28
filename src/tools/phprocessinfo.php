<?php

if (trim(`whoami`) !== "root") {
    sys_abort("请使用root权限运行");
}

if (PHP_OS === "Darwin") {
    sys_abort("Linux Support Only");
}

$pids = explode(" ", trim(`pidof php`));

foreach ($pids as $pid) {
    echo $pid, "\n";
    echo `sudo lsof -p $pid`;
//    echo `ss -p $pid`;
}


$lines = explode(PHP_EOL, trim(`sudo lsof -n|awk '{print \$2}'|sort|uniq -c|sort -nr|head -10`));
foreach ($lines as $line) {
    list($count, $pid) = explode(" ", trim($line));
    echo "pid=$pid,count=$count\n";
}

