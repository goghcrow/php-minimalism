<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/28
 * Time: 上午1:17
 */

use Minimalism\A\Server\Http\Application;
use Minimalism\A\Server\Http\Context;

require __DIR__ . "/../../../vendor/autoload.php";


$app = new Application();
$app->uze(function(Context $ctx) {
    $ctx->body = "Hello World";
});
$app->listen(3000);