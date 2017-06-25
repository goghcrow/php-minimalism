<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/6/16
 * Time: 上午1:49
 */

namespace Minimalism\Test\Event;


use Minimalism\Event\EventLoop;
use Minimalism\Event\Process;

require __DIR__ . "/../../src/Event/EventLoop.php";
require __DIR__ . "/../../src/Event/Process.php";

$ev = new EventLoop();

$proc = new Process($ev, function(Process $process) {

     $path = trim(`which tcpdump`);
     $process->exec($path, ["-i", "any", "-s", "0","-U", "-w", "-"]);

//    $path = trim(`which ls`);
//    $process->exec($path, ["-al"]);
}, function($recv) {
    var_dump($recv);
});
$proc->run();


return;
