<?php

namespace Minimalism\Test;


use Minimalism\Terminal as T;
use Minimalism\Terminal;

require __DIR__ . "/../src/Terminal.php";

$t = new Terminal();

echo "123\n";

$t->cursorUp();
$t->cursorForward(1);

$t->setGraphicsMode(Terminal::FG_RED);
while (true) {
    echo time() & 1;
    $t->cursorBackward(1);
    sleep(1);
}