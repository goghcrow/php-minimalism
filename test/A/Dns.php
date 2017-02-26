<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/1/31
 * Time: 下午1:44
 */

namespace Minimalism\Test\A;


use function Minimalism\A\Client\async_dns_loohup;
use function Minimalism\A\Core\async;
use Minimalism\A\Core\Exception\AsyncTimeoutException;

require __DIR__ . "/../../vendor/autoload.php";

async(function() {
    try {
        yield async_dns_loohup("www.baidu.com", 1);
    } catch (AsyncTimeoutException $e) {
        echo $e;
    }

    echo "\n";
    $ip = (yield async_dns_loohup("www.baidu.com", 100));
    echo $ip;
});

