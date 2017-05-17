<?php

use Minimalism\PHPDump\Util\T;

function sys_echo($s, $sec = null, $usec = null) {
    if ($sec === null) {
        $time = date("H:i:s");
    } else {
        $time = date("H:i:s", $sec);
    }
    if ($sec !== null && $usec !== null) {
        $time .= ".$usec";
    }
    echo "$time $s\n";
}

function sys_error($s) {
    $time = date("H:i:s");
    $s = T::format($s, T::FG_RED);
    fprintf(STDERR, "$time $s\n");
}

function sys_abort($s)
{
    T::error($s, T::FG_RED, T::BRIGHT);
    fprintf(STDERR, "\n\n");
    exit(1);
}

function is_big_endian()
{
    // return bin2hex(pack("L", 0x12345678)[0]) === "12";
    // L ulong 32，machine byte order
    return ord(pack("H2", bin2hex(pack("L", 0x12345678)))) === 0x12;
}