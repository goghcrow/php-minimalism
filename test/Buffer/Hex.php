<?php

namespace Minimalism\Test;



use Minimalism\Buffer\Hex;

require __DIR__ . "/../../src/Buffer/Hex.php";

$str = file_get_contents(__FILE__);

Hex::dump($str);
Hex::dump($str, "v");
Hex::dump($str, "v/16/4");
Hex::dump($str, "vv");
Hex::dump($str, "vv/16/6");
Hex::dump($str, "vvv");
Hex::dump($str, "vvv/8/6");


