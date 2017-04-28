<?php
namespace Minimalism\Test\A;

use function Minimalism\A\Client\async_dns_lookup;
use Minimalism\A\Client\AsyncDns;
use Minimalism\A\Client\AsyncHttpClient;
use function Minimalism\A\Core\go;
use function Minimalism\A\Core\new_;

require __DIR__ . "/../../vendor/autoload.php";

class T1
{
    public $ip;

    public function __construct()
    {
        $this->ip = (yield async_dns_lookup("www.youzan.com"));
    }
}

class T2 extends T1
{
    public function __construct()
    {
        yield parent::__construct();
    }
}


class TAsync extends AsyncDns
{
    public function __construct($host, $timeout)
    {
        parent::__construct($host, $timeout);
        yield \Minimalism\A\Client\async_sleep(1000);
    }
}

go(function() {
    $t2 = (yield new_(T2::class));
    var_dump($t2->ip);

    list($tAsync) = (yield new_(TAsync::class, "www.youzan.com", 1000));
    var_dump($tAsync);
});
