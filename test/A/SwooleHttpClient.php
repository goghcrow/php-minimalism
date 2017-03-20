<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/7
 * Time: 下午10:25
 */

namespace Minimalism\Test\A;



use function Minimalism\A\Client\async_dns_lookup;
use Minimalism\A\Client\SwooleHttpClient;
use function Minimalism\A\Core\spawn;

require __DIR__ . "/../../vendor/autoload.php";

spawn(function() {
    try {
        $ip = (yield async_dns_lookup("www.baidu.com"));
        $cli = new SwooleHttpClient($ip, 80);
        $cli = (yield $cli->awaitGet("/"));
        echo $cli->body, "\n";
    } catch (\Exception $ex) {
        echo $ex;
    } finally {
        swoole_event_exit();
    }
});

