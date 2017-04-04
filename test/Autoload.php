<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/7
 * Time: 上午10:44
 */

namespace Minimalism\Test;

use Minimalism\A\Async;
use Minimalism\A\AsyncHttpClient;
use Minimalism\A\Core\Task;
use Minimalism\Autoload;

require __DIR__ . "/../src/Autoload.php";


$dirs = [
    __DIR__ . "/..",
    "http://gitlab.qima-inc.com/chuxiaofeng/_/raw/master"
];

// $path 按照dirs优先级匹配
$psr4 = [
    "Minimalism\\" => "src",
];
new Autoload($dirs, $psr4);