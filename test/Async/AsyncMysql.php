<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/7
 * Time: 下午10:25
 */

namespace Minimalism\Test\AsyncTask;


use Minimalism\Async\Async;
use Minimalism\Async\AsyncMysql;
use Minimalism\Async\AsyncTimeoutException;

require __DIR__ . "/../../vendor/autoload.php";


function testTimeout()
{
    $mysql = new AsyncMysql();
    try {
        (yield $mysql->connect());
        (yield $mysql->query("select sleep(2)"));
    } catch (\Exception $ex) {
        assert($ex instanceof AsyncTimeoutException);
    } finally {
        $mysql->close();
    }
}
testTimeout();



function testSelect1()
{
    Async::exec(function() {
        $mysql = new AsyncMysql();

        try {
            (yield $mysql->connect());
            (yield $mysql->begin());
            $r = (yield $mysql->query("select 1"));
            (yield $mysql->commit());
//            (yield $mysql->rollback());
            assert($r[0][1] === "1");
        } catch (\Exception $ex) {
            assert(false);
            echo $ex;
        } finally {
            $mysql->close();
        }
    });
}
testSelect1();