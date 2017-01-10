<?php

namespace Minimalism\Test\Config;


use Minimalism\Config\Config;

require __DIR__ . "/../../src/Config/Config.php";


Config::load(__DIR__ . "/ExampleConfig", "dev");

assert(Config::get());
assert(Config::get("store.mysql.master.port") === 3306);
assert(Config::get("store.mysql.cluster1.password") === "pwd_cluster1");
assert(Config::get("dict.App") === "Config");

Config::set("store.mysql.cluster1.password", "new_password");
assert(Config::get("store.mysql.cluster1.password") === "new_password");

Config::set("dict.x.y.not_exist", "hello");
assert(Config::get("dict.x.y.not_exist") === "hello");

Config::load(__DIR__ . "/ExampleConfig", "test");
assert(Config::get());
assert(Config::get("store.mysql.master.user") === "test_user");
assert(Config::get("dict.App") === "Config");

Config::load(__DIR__ . "/ExampleConfig", "product");
assert(Config::get());
assert(Config::get("store.mysql.master.user") === "product_user");
assert(Config::get("dict.App") === "Config");
