<?php

/**
 * 打包phar
 * 必须在php.ini中设置
 * ini_set("phar.readonly", false);
 */

// example

$fromDir = __DIR__ . "/src";
$outDir = __DIR__ . "/phar";

$file = "xxx.phar";
$bootstrap = "bootstrap.php";


$main =<<<PHP
#!/usr/bin/env php
<?php
Phar::mapPhar('{$file}');
require 'phar://{$file}/$bootstrap';   // 加载初始化文件 
__HALT_COMPILER();                     // 中断编译器编译
PHP;


$phar = new Phar("$outDir/$file", 0, $file);
$phar->startBuffering();
$phar->buildFromDirectory($fromDir, '/\.php$/');
$phar->setStub($main);
$phar->compressFiles(Phar::GZ);
$phar->stopBuffering();

chmod("$outDir/$file", 0777);
