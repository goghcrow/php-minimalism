<?php

namespace Minimalism\Test\Sendfile;

use Minimalism\Sendfile\DataUrl;

require __DIR__ . "/../../src/Sendfile/DataUrl.php";

if (php_sapi_name() === "cli") {
    return;
}

$sf = new DataUrl;
for ($n=0; $n < 2; $n++) {
    $str = "<?php\necho '你好{$n}';";
    $sf->attach("f{$n}.php", $str);
    $sf->attachZip("f{$n}.php.zip", $str);
}
$sf->send();
