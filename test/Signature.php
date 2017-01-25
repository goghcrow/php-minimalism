<?php

namespace Minimalism\Test;

use Minimalism\RandStr;
use Minimalism\Signature;
use Minimalism\SignatureException;

require __DIR__ . "/../src/Signature.php";
require __DIR__ . "/../src/RandStr.php";

// TODO 实现的有问题!!!

Signature::setSalt([RandStr::gen(6), RandStr::gen(6), RandStr::gen(6), RandStr::gen(6), RandStr::gen(6)]);

$request = [];
$request["hello"] = "world";
$request["server"] = $_SERVER;
$request["env"] = $_ENV;


Signature::sign($request);
//var_export($request);

$raw = json_encode($request);
Signature::auth($raw);


try {
    sleep(2);
    Signature::auth($raw, 1000);
    assert(false);
} catch (SignatureException $e) {
    assert(true);
}


