<?php

namespace Blue\Test;


use Blue\SimpleHttp;

require __DIR__ . "/../src/SimpleHttp.php";

echo SimpleHttp::get("http://www.baidu.com", [], [])["body"];

echo SimpleHttp::post("http://www.baidu.com", [], null)["body"];