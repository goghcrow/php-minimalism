<?php

if (!isset($argv)) {
    fprintf(STDERR, "Must be run on command line");
    exit(1);
}

if (!isset($argv[3])) {
    // 输出phar文件 stub文件 ...其他文件
    fprintf(STDERR, "USAGE: %s archive_name stubfile source1 [source2...]" . PHP_EOL, $argv[0]);
    exit(2);
}

$buildDir = __DIR__ . "/build";
@mkdir($buildDir);

if (!file_exists($buildDir) || !is_dir($buildDir)) {
    fprintf(STDERR, "mkdir: cannot create directory `$buildDir': File exists");
    exit(3);
}

$pharFile = $argv[1];
$stubFile = $argv[2];

$stubScript =<<<PHP
#!/usr/bin/env php
<?php
Phar::mapPhar('{$pharFile}');
require 'phar://{$pharFile}/{$stubFile}'; // 加载初始化文件 
__HALT_COMPILER();                        // 中断编译器编译

PHP;

$stubScript = str_replace("\r\n", "\n", $stubScript);

$pharFile = "$buildDir/$pharFile";

$phar = new Phar($pharFile);

$phar->setSignatureAlgorithm(\Phar::SHA1);

$phar->startBuffering();

$sourceFiles = array_slice($argv, 2);
foreach ($sourceFiles as $file) {
    $phar->addFile(__DIR__ . "/$file", $file);
}

$phar->addFile(__DIR__ . "/$stubFile", $stubFile);

$phar->setStub($stubScript);
// $phar->setStub($phar->createDefaultStub($stub)); // 这种方式无法添加 #!/usr/bin/env php

$phar->stopBuffering();

chmod($pharFile, 0777);

unset($phar);

function buildFromDir(Phar $phar, $dir, $stub)
{
    $phar->startBuffering();
    $phar->buildFromDirectory($dir, '/\.php$/');
    $phar->setStub($stub);
    $phar->compressFiles(Phar::GZ);
    $phar->stopBuffering();
}
