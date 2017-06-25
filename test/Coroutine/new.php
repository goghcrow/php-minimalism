<?php

namespace Minimalism\Test\Coroutine;

use function Minimalism\Coroutine\callcc;
use function Minimalism\Coroutine\go;
use function Minimalism\Coroutine\new_yield_ctor;

require __DIR__ . "/../../vendor/autoload.php";


class T1
{
    public $ip;

    public function __construct()
    {
        $this->ip = (yield callcc(function($k) {
            swoole_async_dns_lookup("www.google.com", function($host, $ip) use($k) {
                $k($ip);
            });
        }));
    }
}

class T2 extends T1
{
    public function __construct()
    {
        yield parent::__construct();
    }
}


go(function() {
    $t2 = (yield new_yield_ctor(T2::class));
    assert(ip2long($t2->ip));
});
