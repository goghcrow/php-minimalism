<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/17
 * Time: 上午2:41
 */

namespace Minimalism\Test\Benchmark;


use Minimalism\Benchmark\Benchmark;
use Minimalism\Benchmark\Config;
use Minimalism\Benchmark\MysqlTestPlan;

require __DIR__ . "/../../vendor/autoload.php";

class MysqlBench extends MysqlTestPlan
{

    /**
     * Payload Factory
     * @param \swoole_mysql $client
     * @return string sql
     */
    public function payload($client)
    {
        return "select 1";
    }

    /**
     * Receive Assert
     * @param \swoole_mysql $client
     * @param mixed $recv
     * @return bool
     */
    public function assert($client, $recv)
    {
        return $recv[0][1] === "1";
    }

    /**
     * Test Config
     * @return Config
     */
    public function config()
    {
        $conf = new Config("127.0.0.1", 3306, 8, null);
        $conf->label = "mysql-bench";
        $conf->connTimeout = null;
        $conf->recvTimeout = null;

        return $conf;

    }
}

$user = "root";
$password = "";
$database = "test";
$charset = "utf8mb4";

Benchmark::start(new MysqlBench($user, $password, $database, $charset));