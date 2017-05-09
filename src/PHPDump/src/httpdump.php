<?php

namespace Minimalism\PHPDump;

use Minimalism\PHPDump\Http\HttpProtocol;
use Minimalism\PHPDump\Pcap\Pcap;


$self = __FILE__;
if (isset($argv[1]) && $argv[1] === "install") {
    `chmod +x $self && cp $self /usr/local/bin/novadump`;
    exit();
}

if (trim(`whoami`) !== "root") {
    sys_abort("请使用root权限运行");
}

if (PHP_OS === "Darwin") {
    sys_abort("暂时只支持Linux");
}


Pcap::registerProtocol(new HttpProtocol());

$phpDump = new PHPDump();
$phpDump->readTcpdump("tcp");