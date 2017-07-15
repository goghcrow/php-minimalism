<?php

$pid = $argv[1];
$cmd = 'gdb -p ' . $pid . ' --batch -eval-command=\'set $code = "try { eval(\"echo 42;\");}catch(\Throwable $t){echo $t;}catch (\Exception $e){echo $t;}"\' -eval-command=\'call zend_eval_string($code, 0, "inject.php")\'';
echo `$cmd`;


//$pid = $argv[1];
//$file = $argv[2];
//$file = "/data/users/chuxiaofeng/hello.php";
//
//$payload = [
//    '',
//    '',
//];
//$payload = implode("", $payload);

//$tmp_file = "/tmp/inject.php";
//file_put_contents($tmp_file, $payload);
//register_shutdown_function(function() use($tmp_file) {
//    @unlink($tmp_file);
//});

// $php_bin=`which php`;
//$php_bin = PHP_BINARY;
// `eval "gdb --batch -nx $php_bin $pid -ex \"source ${FILE}\" 2>/dev/null"`;
//`gdb -p $pid --batch -eval-command='call zend_eval_string("$payload", 0, "")'`;
//echo `gdb --batch -nx $php_bin $pid -ex "source $tmp_file"`;
