<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/7
 * Time: 下午10:25
 */

namespace Minimalism\Test\A;



use Minimalism\A\Client\AsyncMysql;
use function Minimalism\A\Core\spawn;
use Minimalism\A\Core\Exception\AsyncTimeoutException;

require __DIR__ . "/../../vendor/autoload.php";


function randstr($length = 1024)
{
    $f = fopen("/dev/urandom", "r");
    $r = fread($f, $length);
    fclose($f);

    $isUtf8 = preg_match('//u', $r);
    if (!$isUtf8) {
        $sanitize = function ($m) { return utf8_encode($m[0]); };
        $r = preg_replace_callback('/[\x80-\xFF]+/', $sanitize, $r);
    }
    if (strlen($r) > $length) {
        $r = substr($r, 0, $length);
    } else {
        $r = str_pad($r, $length);
    }
    return $r;
}


function testTimeout()
{
    spawn(function() {
        $mysql = new AsyncMysql();
        $ex = null;
        try {
            (yield $mysql->connect());
            (yield $mysql->query("select sleep(1)", [], 500));
            assert(false);
        } catch (\Exception $ex) {
        } finally {
            assert($ex instanceof AsyncTimeoutException);
            $mysql->close();
        }
    });
}
testTimeout();


function testSelect1()
{
    spawn(function() {
        $mysql = new AsyncMysql();
        try {
            yield $mysql->connect();
            $r = (yield $mysql->query("select 1"));
            assert($r[0][1] === "1");
        } catch (\Exception $ex) {
            assert(false);
            fprintf(STDERR, $ex);
        } finally {
            $mysql->close();
        }
    });
}
testSelect1();



function insert($commit = true)
{
    $mysql = new AsyncMysql();

    try {
        yield $mysql->connect();
        yield $mysql->begin();

        $r = (yield $mysql->query("DROP TABLE IF EXISTS `tmp`;"));
        assert($r === true);

        $r = (yield $mysql->query("CREATE TABLE `tmp` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `text` longtext,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"));
        assert($r === true);


        yield $mysql->begin();
        $value = str_repeat("\0", 1024 * 1024 * 3.9); // max_allowed_packet 4m
        $r = (yield $mysql->query("insert into tmp (`text`) VALUE ('$value')"));
        assert($r === true);

        $id = $mysql->insert_id;

        if ($commit) {
            yield $mysql->commit();
            $r = (yield $mysql->query("select * from tmp where `id` = $id"));
            assert($r[0]["text"] === $value);
        } else {
            yield $mysql->rollback();
            $r = (yield $mysql->query("select id from tmp where `id` = $id"));
            assert($r === []);
        }

        $r = (yield $mysql->query("DROP TABLE IF EXISTS `tmp`;"));
        assert($r === true);

    } catch (\Exception $ex) {
        assert(false);
        echo $ex;
    } finally {
        $mysql->close();
    }
}


function testTransactionTask() {
    yield insert(true);

    yield insert(false);
}

function testTransaction()
{
    spawn(testTransactionTask());
}
testTransaction();


swoole_timer_after(1000, function() { swoole_event_exit(); });