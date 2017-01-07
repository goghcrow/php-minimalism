<?php

namespace Minimalism\Test;

use Minimalism\Import;
use Minimalism\RandStr;

require __DIR__ . "/../src/Import.php";

Import::github("https://raw.githubusercontent.com/goghcrow/php-deps/master/src/RandStr.php");

echo RandStr::gen(10);