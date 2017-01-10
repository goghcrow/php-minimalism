<?php

namespace Minimalism\Test\functions;

use function Minimalism\functions\import;
use Minimalism\RandStr;

require __DIR__ . "/../../src/functions/import.php";

import("https://raw.githubusercontent.com/goghcrow/php-deps/master/src/RandStr.php");

echo RandStr::gen(10);