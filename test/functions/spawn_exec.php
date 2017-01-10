<?php

namespace Minimalism\Test\functions;

use function Minimalism\functions\spawn_exec;

require __DIR__ . "/../../src/functions/spawn_exec.php";


list($out, $err) = spawn_exec("exec php", "<?php phpinfo();");
assert(strlen($out));
// echo $err;

list($out, $err) = spawn_exec("ls -al");
assert(strlen($out));
assert(!$err);


list($out, $err) = spawn_exec("xxx");
assert(!$out);
assert(strlen($err));


try {
    list($out, $err) = spawn_exec("sleep 10", null, 1);
    assert(false);
    echo $out, $err;
} catch (\Exception $ex) {
    assert(true);
}
