<?php

namespace Minimalism\Test\Coroutine;

use function Minimalism\Coroutine\next_tick;

require __DIR__ . "/../../vendor/autoload.php";

$test = function() {
    $r = "";
    next_tick(function() use(&$r) {
        next_tick(function() use(&$r) {
            $r.= "2";
            next_tick(function() use(&$r) {
                $r .= "3";
                assert($r === "0123");
            });
        });
        $r .= "1";
    });
    $r .= "0";
};
$test();
