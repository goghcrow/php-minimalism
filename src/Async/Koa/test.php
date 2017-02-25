<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/26
 * Time: 上午12:03
 */

namespace Minimalism\Async\Koa;
use Minimalism\Async\Async;



// TODO
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

Async::exec($task);
