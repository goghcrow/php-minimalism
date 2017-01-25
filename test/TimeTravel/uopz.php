<?php

namespace Minimalism\Test\TimeTravel;

use function Minimalism\TimeTravel\time_travel;

require __DIR__ . "/../../src/TimeTravel/uopz.php";


$sec = -10 * 60;
$now = time();
time_travel(10 * 60);
assert($now - time() === $sec);

// strtotime();
// date();





