<?php

namespace Minimalism\Test;

use function Minimalism\import;
use Minimalism\RandStr;

require __DIR__ . "/../src/Import.php";

import("https://raw.githubusercontent.com/goghcrow/php-deps/master/src/RandStr.php");

echo RandStr::gen(10);