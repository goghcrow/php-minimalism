<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/17
 * Time: 上午12:59
 */

namespace Minimalism\Benchmark;


abstract class MysqlTestPlan implements TestPlan
{
    public $user;
    public $password;
    public $database;
    public $charset;

    public function __construct($user = "root", $password = "", $database = "test", $charset = "utf8")
    {
        $this->user = $user;
        $this->password = $password;
        $this->database = $database;
        $this->charset = $charset;
    }

    /**
     * Payload Factory
     * @param \swoole_mysql $client
     * @return string sql
     */
    abstract public function payload($client);

    /**
     * Receive Assert
     * @param \swoole_mysql $client
     * @param mixed $recv
     * @return bool
     */
    abstract public function assert($client, $recv);
}