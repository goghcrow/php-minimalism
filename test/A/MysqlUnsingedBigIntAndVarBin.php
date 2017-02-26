<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/8
 * Time: 下午9:27
 */

namespace Minimalism\Test\A;



use Minimalism\A\Client\AsyncMysql;
use function Minimalism\A\Core\async;

require __DIR__ . "/../../vendor/autoload.php";

// PHP_INT_MAX, 9223372036854775807;
define("MAX_UNSIGNED_BIGINT", "18446744073709551615");


async(function() {
    $mysql = new AsyncMysql();
    try {
        yield $mysql->connect();

        yield $mysql->query("DROP TABLE IF EXISTS `type_test`;");

        yield $mysql->query("CREATE TABLE `type_test` (
  `id` bigint(20) unsigned DEFAULT NULL,
  `varbin` varbinary(1024) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

        yield $mysql->query("SET sql_mode = 'NO_UNSIGNED_SUBTRACTION';");

        $id = MAX_UNSIGNED_BIGINT;
        $varbin = fread(fopen("/dev/urandom", "r"), 1024);

        yield $mysql->begin();

        yield $mysql->query("insert into `type_test` (`id`, `varbin`) values ($id, '" . addslashes($varbin) . "')");

        $r = (yield $mysql->query("select `id`, `varbin` from `type_test` where id = $id"));
        assert($r[0]["id"] === $id);
        assert(strlen($r[0]["varbin"]) === 1024 && $r[0]["varbin"] === $varbin);

        // yield $mysql->commit();
        yield $mysql->rollback();

        yield $mysql->query("DROP TABLE IF EXISTS `type_test`;");

    } catch (\Exception $ex) {
        fprintf(STDERR, $ex);
    } finally {
        $mysql->close();
        swoole_event_exit();
    }
});