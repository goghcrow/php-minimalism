<?php

namespace Minimalism\Test;


use Minimalism\Extension;

require __DIR__ . "/../src/Extension.php";


//ReflectionExtension::export("swoole");
Extension::export("swoole", fopen("swoole.php", "w+"));
Extension::export("SPL", fopen("SPL.php", "w+"));
