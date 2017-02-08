<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/1/31
 * Time: 下午3:25
 */

namespace Minimalism\Async;


/**
 * Class AsyncMysql
 * @package Minimalism\Async
 *
 * @property int $sock 连接使用的文件描述符
 * @property bool $connected 是否连接上了MySQL服务器
 * @property int $errno 最后一次错误
 * @property string $error 最后一次错误的描述
 * @property int $affected_rows 影响的行数
 * @property int $insert_id 最后一个插入的记录id
 */
class AsyncMysql extends AsyncWithTimeout
{
    /** @var callable  */
    public $k;
    public $mysql;
    public $config;
    public $sql;
    public $bind;

    public function __construct(array $config = [])
    {
        $this->config = $config + [
                "host" => "127.0.0.1",
                "port" => 3306,
                "user" => "root",
                "password" => "",
                "database" => "test",
                "charset" => "utf8mb4",
            ];

        $this->mysql = new \swoole_mysql();
        $this->mysql->on("connect", [$this, "onConnect"]);
        $this->mysql->on("close", [$this, "onClose"]);
        $this->mysql->on("error", [$this, "onError"]);

        $this->k = [$this, "doConnect"];
    }

    public function __get($name)
    {
        return $this->mysql->$name;
    }

    public function __call($name, $arguments)
    {
        // TODO: Implement __call() method.
    }

    public function connect($timeout = 1000)
    {
        $this->timeout = $timeout;
        $this->k = [$this, "doConnect"];
        return $this;
    }

    public function query($sql, array $bind = [], $timeout = 1000)
    {
        $this->timeout = $timeout;
        $this->sql = $sql;
        $this->bind = $bind;
        $this->k = [$this, "doQuery"];
        return $this;
    }

    public function begin($timeout = 1000)
    {
        $this->timeout = $timeout;
        $this->k = function() {
            $this->mysql->begin([$this, "onTransaction"]);
        };
        return $this;
    }

    public function commit($timeout = 1000)
    {
        $this->timeout = $timeout;
        $this->k = function() {
            $this->mysql->commit([$this, "onTransaction"]);
        };
        return $this;
    }

    public function rollback($timeout = 1000)
    {
        $this->timeout = $timeout;
        $this->k = function() {
            $this->mysql->rollback([$this, "onTransaction"]);
        };
        return $this;
    }

    public function close()
    {
        $this->mysql->close();
    }

    // ⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇internal⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇⬇

    public function onConnect(\swoole_mysql $mysql) {
        if ($mysql->errno) {
            $this->ithrow();
        } else {
            $this->returnVal($mysql);
        }
    }

    public function onClose(/* \swoole_mysql $mysql */)
    {
        $this->ithrow();
    }

    public function onError(/* \swoole_mysql $mysql */)
    {
        $this->ithrow();
    }

    public function doConnect()
    {
        $r = $this->mysql->connect([
            "host" => $this->config["host"],
            "port" => $this->config["port"],
            "user" => $this->config["user"],
            "password" => $this->config["password"],
            "database" => $this->config["database"],
            "charset" => $this->config["charset"],
        ]);
        if (!$r) {
            $this->ithrow();
        }
    }

    public function doQuery()
    {
        $this->mysql->query($this->sql, $this->bind,
            function(\swoole_mysql $mysql, $r) {
                if ($mysql->errno) {
                    $this->ithrow();
                } else {
                    $this->returnVal($r);
                }
        });
    }

    public function onTransaction(\swoole_mysql $mysql)
    {
        if ($mysql->errno) {
            $this->ithrow();
        } else {
            $this->returnVal(true);
        }
    }

    protected function execute()
    {
        $k = $this->k;
        $k();
    }

    private function ithrow()
    {
        $this->throwEx(new AsyncMysqlException($this->mysql->error, $this->mysql->errno));
    }
}