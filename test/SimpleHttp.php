<?php

namespace Minimalism\Test;


use Minimalism\SimpleHttp;

require __DIR__ . "/../src/SimpleHttp.php";

echo SimpleHttp::get("http://www.baidu.com", [], [])["body"];

echo SimpleHttp::post("http://www.baidu.com", [], null)["body"];