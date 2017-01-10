<?php

namespace Minimalism\Test\Sendfile;


use Minimalism\Sendfile\ZipArch;

require __DIR__ . "/../../src/Sendfile/ZipArch.php";

if (php_sapi_name() === "cli") {
    return;
}

$zip = new ZipArch();
$zip->attachString("a.txt", "hello");
$zip->attachFile("zipArch", __FILE__);
$zip->send("test.zip");
