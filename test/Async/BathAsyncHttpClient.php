<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/15
 * Time: 下午7:09
 */

namespace Minimalism\Test\AsyncTask;

use Minimalism\Async\Async;
use Minimalism\Async\AsyncDns;
use Minimalism\Async\AsyncHttpClient;
use Minimalism\Async\Core\AsyncTask;

require __DIR__ . "/../../vendor/autoload.php";


function serial()
{
    Async::exec(function() {
        try {
            $r = (yield (new AsyncHttpClient("10.9.80.209", 4161))
                ->setMethod("GET")
                ->setUri("/lookup?topic=zan_mqworker_test")
                ->setTimeout(3000));
            // var_dump($r->body);
            echo memory_get_usage(), "\n";
        } catch (\Exception $ex) {
            echo $ex;
        } finally {
            serial();
        }
    });
}


for ($i = 0; $i < 50; $i++) {
    serial();
}
