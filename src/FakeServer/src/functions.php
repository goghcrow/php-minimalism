<?php

function sys_echo($s)
{
    $workerId = isset($_SERVER["WORKER_ID"]) ? $_SERVER["WORKER_ID"] : "";
    $dataStr = date("Y-m-d H:i:s");
    echo "[$dataStr #$workerId] $s\n";
}

function sys_error($s)
{
    $s = str_replace("%", "%%", $s);
    $workerId = isset($_SERVER["WORKER_ID"]) ? $_SERVER["WORKER_ID"] : "";
    $dataStr = date("Y-m-d H:i:s");
    fprintf(STDERR, "[$dataStr #$workerId] $s\n");
}

function sys_abort($s)
{
    $s = str_replace("%", "%%", $s);
    fprintf(STDERR, "$s\n");
    echo new \Exception();
    exit(1);
}