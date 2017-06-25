<?php

namespace Minimalism\Test\Coroutine;

use function Minimalism\Coroutine\defer;

require __DIR__ . "/../../vendor/autoload.php";

$test = function() {
    $r = "";
    defer(function() use(&$r) {
        defer(function() use(&$r) {
            $r.= "2";
            defer(function() use(&$r) {
                $r .= "3";
                assert($r === "0123");
            });
        });
        $r .= "1";
    });
    $r .= "0";
};
$test();
