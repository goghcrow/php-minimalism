<?php

namespace Minimalism\PHPDump;

use Minimalism\PHPDump\Http\HttpDissector;
use Minimalism\PHPDump\Pcap\Pcap;

ini_set("memory_limit", -1);
require __DIR__ . "/../test-load.php";

$servicePattern = null;
$methodPattern = null;
$exportFile = null;
$pcapFile = __DIR__ . "/../log.pcap";

Pcap::registerProtocol(new HttpDissector());


$phpDump = new PHPDump();
$phpDump->readFile($pcapFile);
