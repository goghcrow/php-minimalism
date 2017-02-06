<?php

namespace Minimalism\Test\Config;


use Minimalism\Config\Config;

require __DIR__ . "/../../src/Config/Config.php";


Config::load(__DIR__ . "/config", "online");
echo Config::toIni();
