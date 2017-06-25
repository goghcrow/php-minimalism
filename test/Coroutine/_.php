<?php

namespace Minimalism\Test\Coroutine;

require __DIR__ . "/../../vendor/autoload.php";



$regex = '/.*\.php$/';
$iter = new \RecursiveDirectoryIterator(__DIR__, \RecursiveDirectoryIterator::SKIP_DOTS);
$iter = new \RecursiveIteratorIterator($iter, \RecursiveIteratorIterator::LEAVES_ONLY);
$iter = new \RegexIterator($iter, $regex, \RegexIterator::GET_MATCH);

$pids = [];
foreach ($iter as $file) {
    $file = realpath($file[0]);
    if ($file === __FILE__) {
        continue;
    }


    $pid = pcntl_fork();
    if ($pid < 0) {
        fprintf(STDERR, "fork fail");
        exit(255);
    }

    if ($pid === 0) {
        register_shutdown_function(function() use($file) {
            $error = error_get_last();
            if ($error) {
                fprintf(STDERR, $file . "\n");
            }
        });
        require $file;
        exit;
    } else {
        $pids[$pid] = $file;
    }
}

foreach ($pids as $pid => $file) {
    pcntl_waitpid($pid, $status);
    echo "$status [$pid] $file\n";
}
