<?php

use Minimalism\PHPDump\Util\T;

function sys_echo($s, $sec = null, $usec = null)
{
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

function sys_error($s)
{
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

function read_line($prompt = "", $len = 1024)
{
//    if (function_exists("readline")) {
//        return rtrim(readline($prompt), "\r\n");
//    } else {
        if ($prompt !== "") {
            echo $prompt;
        }
        return stream_get_line(STDIN, $len, PHP_EOL);
//    }
}

/**
 * @param int $argType 1 基本类型  2 忽略 3 全部
 * @return string
 */
function backtrace($argType = 1)
{
    switch ($argType) {
        case 2:
            ob_start();
            debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            return ob_get_clean();

        case 3:
            ob_start();
            debug_print_backtrace();
            return ob_get_clean();

        case 1:
        default:
            return (new \Exception)->getTraceAsString();
    }
}