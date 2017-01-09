<?php

namespace Minimalism\Test;


use Minimalism\Argv;

require __DIR__ . "/../src/Argv.php";


$options = [
    ["v", "verbose", "print verbose information"],
    ["c", "verbose", "print verbose information"],
];

$args = Argv::parse($options);
var_dump($args->get());
$args->usage("hello");


