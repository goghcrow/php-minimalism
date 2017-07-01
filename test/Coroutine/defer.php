<?php

namespace Minimalism\Test\Coroutine;

use function Minimalism\Coroutine\fork;
use function Minimalism\Coroutine\defer;
use function Minimalism\Coroutine\future;
use function Minimalism\Coroutine\go;
use Minimalism\Coroutine\Time;

require __DIR__ . "/../../vendor/autoload.php";


call_user_func(function() {
    $var = 0;
    go(function() use(&$var) {
        yield defer(function() use(&$var) {
            assert($var === 0);
            // echo "a";
            $var = "a";
        });

        yield fork(function() use(&$var) {
            yield defer(function() use(&$var)  {
                assert($var === "a");
                // echo "b";
                $var = "b";
            });

            yield Time::sleep(1);
        });

        yield future(function() use(&$var) {
            yield defer(function() use(&$var) {
                assert($var === "b");
                // echo "c";
                $var = "c";
            });
            yield Time::sleep(1);
        });
    });
});