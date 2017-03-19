<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/3/5
 * Time: 下午6:24
 */

namespace Minimalism\Test\A;

use function Minimalism\A\Client\async_dns_lookup;
use function Minimalism\A\Client\async_sleep;
use function Minimalism\A\Core\async;
use function Minimalism\A\Core\callcc;
use function Minimalism\A\Core\race;

require __DIR__ . "/../../vendor/autoload.php";


// 一个race处理超时的示例
async(function() {

    function dnslookup($host) {
        return callcc(function($k) use($host) {
            swoole_async_dns_lookup($host, function($host, $ip) use($k) {
                $k($ip);
            });
        });
    }

    function timeout($ms)
    {
        yield async_sleep($ms);
        throw new \Exception("timeout");
    }


    // 更换域名避免缓存影响, 否则查询耗时<1ms, 影响测试


    // -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

    $timeout = async_sleep(100);
    $dnslookup = dnslookup("www.baidu.com");

    $r = (yield race([
        $timeout,
        $dnslookup,
    ]));
    assert(ip2long($r));

    // -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

    $timeout = async_sleep(1);
    $dnslookup = dnslookup("www.google.com");

    $r = (yield race([
        $timeout,
        $dnslookup,
    ]));
    assert($r === null); // 超时直接返回 null


    // -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

    $timeout = timeoutWrapper(100);
    $dnslookup = dnslookup("www.baidu.com");
    $r = (yield race([
        $timeout,
        $dnslookup,
    ]));
    assert(ip2long($r));



    // -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

    $timeout = timeoutWrapper(1);
    $dnslookup = dnslookup("www.so.com");

    $ex = null;
    try {
        $r = (yield race([
            $timeout,
            $dnslookup,
        ]));
        assert(false);

    } catch (\Exception $ex) {

    }
    assert($ex && $ex->getMessage() === "timeout");
});

