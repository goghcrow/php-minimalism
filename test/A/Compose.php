<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/27
 * Time: 上午12:30
 */

use function Minimalism\A\Core\async;
use function Minimalism\A\Core\await;
use function Minimalism\A\Server\Http\compose;
use Minimalism\A\Server\Http\Context;

require __DIR__ . "/../../vendor/autoload.php";


async(function() {
    $task = compose([function($next) {
        echo 1;
        /* @var $this Context */
        $this->body = "x";
        yield $next;
        echo 6;
    }, function($next) {
        echo 2;
        $this->body .= "y";
        yield $next;
        echo 5;
    }, function($next) {
        echo 3;
        $this->body .= "z";
        yield $next;
        echo 4;
    }, function($next) {
        echo $this->body;
        yield $next;
    }]);

    // 123xyz456
    yield await($task);
});