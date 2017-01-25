<?php

namespace Minimalism\Test\functions;

use function Minimalism\functions\scan;
use SplFileInfo;

require __DIR__ . "/../../src/functions/scan.php";


$dir = __DIR__ . "/../../src";
$test = __DIR__ . "/../";

print_r(iterator_to_array(scan($dir, '/.*\.php/')));

print_r(iterator_to_array(scan($dir, '/.*\.php/', null, false)));

scan([$dir, $test], '/.*\.php/');
scan("\tmp", null, function(SplFileInfo $current, $path, $iter) { return true; });
scan([$dir, $test], null, function(SplFileInfo $current, $path, $iter) { return true; });
