<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/1/31
 * Time: 下午1:44
 */

namespace Minimalism\Test\A;


use function Minimalism\A\Client\async_dns_lookup;
use function Minimalism\A\Core\spawn;
use Minimalism\A\Core\Exception\AsyncTimeoutException;

require __DIR__ . "/../../vendor/autoload.php";

spawn(function() {
    try {
        yield async_dns_lookup("www.baidu.com", 1);
    } catch (AsyncTimeoutException $e) {
    }
    assert($e instanceof  AsyncTimeoutException);

    $ip = (yield async_dns_lookup("www.baidu.com", 100));
    assert(ip2long($ip));
});

