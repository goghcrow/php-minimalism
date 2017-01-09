<?php

namespace Minimalism\Test;

use Minimalism\PipeChain;

require __DIR__ . "/../src/PipeChain.php";


/** @noinspection PhpUndefinedMethodInspection */
assert(PipeChain::of("hello world")->substr(6)->strtoupper()->get() === "WORLD");

/** @noinspection PhpUndefinedMethodInspection */
assert(PipeChain::of(range(1, 10))->array_sum()->get() === 55);

