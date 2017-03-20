<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/7
 * Time: 下午10:25
 */

namespace Minimalism\Test\A;



use Minimalism\A\Client\SwooleMysqlClient;
use function Minimalism\A\Core\spawn;

require __DIR__ . "/../../vendor/autoload.php";

spawn(function() {
    try {
        $mysql = new SwooleMysqlClient();
        $mysql->on("close", function() { echo "mysql closed\n"; });
        $mysql->on("error", function() { echo "mysql error\n"; });

        yield $mysql->awaitConnect([
            "host" => "127.0.0.1",
            "port" => 3306,
            "user" => "root",
            "password" => "",
            "database" => "test",
            "charset" => "utf8",
        ]);

        assert($mysql->errno === 0);
        $r = (yield $mysql->awaitQuery("select 1"));
        var_dump($r);

    } catch (\Exception $ex) {
        echo $ex;
    } finally {
        $mysql->close();
        swoole_event_exit();
    }

});
