<?php

namespace Minimalism\Test\Config;


use Minimalism\Config\Config;
use Minimalism\Config\ConfigGen;

require __DIR__ . "/../../src/Config/Config.php";
require __DIR__ . "/../../src/Config/ConfigGen.php";

Config::load(__DIR__ . "/ExampleConfig", "dev");

$file = "ConfigObject";
$conf = ConfigGen::requireOnce(Config::get(), __DIR__, "ConfigObject", __NAMESPACE__);

assert($conf);
assert($conf->store->mysql->master->port === 3306);
assert($conf->store->mysql->cluster1->password === "pwd_cluster1");
assert($conf->dict->App === "Config");

$conf->store->mysql->cluster1->password = "new_password";
assert($conf->store->mysql->cluster1->password === "new_password");

unlink(__DIR__ . "/$file.php");