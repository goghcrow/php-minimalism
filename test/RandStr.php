<?php

namespace Blue\Test;

use Blue\RandStr;

require __DIR__ . "/../src/RandStr.php";

echo RandStr::gen(10, RandStr::NUM), "\n";

echo RandStr::gen(10, RandStr::ALPHA), "\n";

echo RandStr::gen(10, RandStr::CHINESE), "\n";

echo RandStr::gen(10, RandStr::NUM | RandStr::ALPHA), "\n";

echo RandStr::gen(10, RandStr::NUM | RandStr::ALPHA | RandStr::CHINESE), "\n";

