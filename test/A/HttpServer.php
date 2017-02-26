<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/27
 * Time: 上午12:25
 */

use Minimalism\A\Server\Http\Application;
use Minimalism\A\Server\Http\Context;
use Minimalism\A\Server\Http\Request;

require __DIR__ . "/../../vendor/autoload.php";


$app = new Application();

$app->uze(function($next) {
    /* @var $req Request */
    $req = $this->req;
    var_dump($req->header);

    /* @var $this Context */
    $start = microtime(true);
    echo 1;

    yield $next;

    echo 6, "\n";
    $end = microtime(true);

    echo $end - $start, "\n";
});

$app->uze(function($next) {
    echo 2;

    yield $next;

    echo 5;
});

$app->uze(function($next) {
    echo 3;

    yield $next;

    $this->code = 200;
    $this->body = "z";

    echo 4;
});
$app->listen();