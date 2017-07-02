<?php


namespace Minimalism\Test\Coroutine;

use function Minimalism\Coroutine\cond;
use function Minimalism\Coroutine\go;
use Minimalism\Coroutine\Time;
use function Minimalism\Coroutine\wg;

require __DIR__ . "/../../vendor/autoload.php";


go(function() {
    $wg = wg(2);

    go(function() use($wg) {
        go(function() use($wg) {
            $cond = cond();

            $count = 0;

            for ($i = 0; $i < 3; $i++) {
                go(function() use($cond, &$count) {
                    yield $cond->wait();
                    $count++;
                });
            }

            yield Time::sleep(1);
            $cond->signal();

            assert($count === 1);

            yield Time::sleep(1);
            $cond->signal();

            assert($count === 2);

            yield Time::sleep(1);
            $cond->signal();

            assert($count === 3);

            $wg->done();
        });
    });



    go(function() use($wg) {
        go(function() use($wg) {
            $cond = cond();

            $count = 0;

            for ($i = 0; $i < 3; $i++) {
                go(function() use($cond, &$count) {
                    yield $cond->wait();
                    $count++;
                });
            }

            yield Time::sleep(1);
            $cond->broadcast();
            assert($count === 3);

            $wg->done();
        });
    });

    yield $wg->wait();

    swoole_event_exit();
});
