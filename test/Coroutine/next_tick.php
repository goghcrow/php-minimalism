<?php

namespace Minimalism\Test\Coroutine;

use function Minimalism\Coroutine\nextTick;

require __DIR__ . "/../../vendor/autoload.php";

$test = function() {
    $r = "";
    nextTick(function() use(&$r) {
        nextTick(function() use(&$r) {
            $r.= "2";
            nextTick(function() use(&$r) {
                $r .= "3";
                assert($r === "0123");
            });
        });
        $r .= "1";
    });
    $r .= "0";
};
$test();
