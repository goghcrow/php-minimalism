<?php

namespace Minimalism\Test\Event;


use Minimalism\Event\EventProxy;

require __DIR__ . "/../../src/Event/EventEmitter.php";
require __DIR__ . "/../../src/Event/EventProxy.php";


class Bz
{
    private $ep;

    public function __construct()
    {
        $this->ep = new EventProxy();
    }

    public function dns()
    {
        $this->ep->all(["baidu", "youzan", "google"], function($ret) {
            var_dump($ret);
        });

        swoole_async_dns_lookup("www.baidu.com", function($host, $ip) {
            $this->ep->emit("baidu", $ip);
        });

        swoole_async_dns_lookup("www.youzan.com", function($host, $ip) {
            $this->ep->emit("youzan", $ip);
        });

        swoole_async_dns_lookup("www.google.com", function($host, $ip) {
            $this->ep->emit("google", $ip);
        });
    }
}

$bz = new Bz();
$bz->dns();





$testAny = function() {
    $ep = new EventProxy();
    $ep->any(function($event, ...$args) {
        assert($event === "event2");
        assert($args === [1, 2, 3]);
    }, "event1", "event2", "event3");

//    $ep->once("event1", function() { echo "once event1\n"; });
//    $ep->on("event2", function() { echo "normal event\n"; });

    assert($ep->emit("event2", 1, 2, 3));
    assert($ep->emit("event1", 1));
    assert(!$ep->emit("event1", 1));
    assert($ep->emit("event3", 2, 3));
};


$testAll = function() {
    $ep = new EventProxy();
    $ep->all(["event1", "event2", "event3"], function($result) {
        assert($result == [
                "event2" => ["result2", 2],
                "event3" => ["result3", 3],
                "event1" => ["result1", 1],
            ]);
    });


    assert($ep->emit("event2", "result2", 2));
    assert($ep->emit("event3", "result3", 3));
    assert($ep->emit("event1", "result1", 1));


    assert(!$ep->emit("event2", "result2"));
    assert(!$ep->emit("event3", "result3"));
    assert(!$ep->emit("event1", "result1"));
};



$allExample = function() {
    $ep = new EventProxy();

    $ep->all(["baidu", "youzan", "google"], function($ret) { var_dump($ret);})
//        ->on(EventProxy::ERROR, function(\Exception $ex) {})
    ;

    swoole_timer_after(1, function() use($ep) {
//        $ep->throwEx(new \Exception());
        $ep->emit("baidu", null);
    });
    swoole_timer_after(1000, function() use($ep) {
        $ep->emit("youzan", null);
    });
    swoole_timer_after(1000, function() use($ep) {
        $ep->emit("google", null);
    });


    swoole_async_dns_lookup("www.baidu.com", function($host, $ip) use($ep) {
        $ep->emit("baidu", $ip);
    });
    swoole_async_dns_lookup("www.youzan.com", function($host, $ip) use($ep) {
        $ep->emit("youzan", $ip);
    });
    swoole_async_dns_lookup("www.google.com", function($host, $ip) use($ep) {
        $ep->emit("google", $ip);
    });
};


$testAny();
$testAll();

$allExample();