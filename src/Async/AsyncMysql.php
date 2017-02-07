<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/1/31
 * Time: 下午3:25
 */

namespace Minimalism\Async;


class AsyncMysql extends AsyncWithTimeout
{
    public $mysql;

    public function __construct()
    {
        $this->mysql = new \swoole_mysql();
        // $this->mysql->on("error");
        // $this->mysql->on("close");
        // $this->mysql->on("connect");
    }

    protected function execute()
    {
        $this->mysql->begin_transaction();
        $this->mysql->commit();
        $this->mysql->rollback();
        $this->mysql->query();
    }

    public function query($sql, array $bind = [])
    {

    }

    public function beginTransaction()
    {

    }

    public function commit()
    {

    }

    public function rollback()
    {

    }
}