<?php

namespace Minimalism\FakeServer;

use Minimalism\FakeServer\MySQL\MySQLConnection;
use Minimalism\FakeServer\MySQL\MySQLField;

require_once __DIR__ . "/../load.php";

$mysqlServer = new \Minimalism\FakeServer\MySQL\FakeMySQLServer(["port" => 7777]);
$mysqlServer->on("login", function(MySQLConnection $connection, array $data)  {
    $connection->authorizeOK();
});
$mysqlServer->on("command", function(MySQLConnection $connection, $cmd, array $args) {
    echo $connection->getCommandName($cmd), "\n";
    var_dump($args);
});
$mysqlServer->on("query", function(MySQLConnection $connection, $sql) {
    echo "sql: $sql\n";

    if ($sql === "SHOW DATABASES") {
        /**
        MySQL Protocol
        Packet Length: 75
        Packet Number: 2

        Catalog: def
        Database: information_schema
        Table: SCHEMATA
        Original table: SCHEMATA
        Name: Database
        Original name: SCHEMA_NAME
        Charset number: utf8 COLLATE utf8_general_ci (33)
        Length: 192
        Type: FIELD_TYPE_VAR_STRING (253)
        Flags: 0x0001
        Decimals: 0
         */
        $f1 = new MySQLField();
        $f1->catalog = "def";
        $f1->database = "information_schema";
        $f1->table = "SCHEMATA";
        $f1->originalTable = "SCHEMATA";
        $f1->name = "Database";
        $f1->originalName = "Database";
        $f1->charset = 0x21;
        $f1->length = 253;
        $f1->type = MySQLField::FIELD_TYPE_VAR_STRING;
        $f1->flags = 0x0001; // not null
        $f1->decimals = 0;

        $fields = [$f1];
        $values = [
            ["Database" => "D1"],
            ["Database" => "D2"],
            ["Database" => "D3"],
        ];

        $connection->writeResultSet($fields, $values);
    } else if ($sql === "show variables like 'lower_case_table_names'") {
        /**
        MySQL Protocol
        Packet Length: 82
        Packet Number: 2
        Catalog: def
        Database:
        Table: session_variables
        Original table: session_variables
        Name: Variable_name
        Original name: Variable_name
        Charset number: utf8 COLLATE utf8_general_ci (33)
        Length: 192
        Type: FIELD_TYPE_VAR_STRING (253)
        Flags: 0x1001
        Decimals: 0
         */
        $f1 = new MySQLField();
        $f1->catalog = "def";
        $f1->database = "";
        $f1->table = "session_variables";
        $f1->originalTable = "session_variables";
        $f1->name = "Variable_name";
        $f1->originalName = "Variable_name";
        $f1->charset = 0x21;
        $f1->length = 192;
        $f1->type = MySQLField::FIELD_TYPE_VAR_STRING;
        $f1->flags = 0x0001; // not null
        $f1->decimals = 0;

        /**
        MySQL Protocol
        Packet Length: 66
        Packet Number: 3
        Catalog: def
        Database:
        Table: session_variables
        Original table: session_variables
        Name: Value
        Original name: Value
        Charset number: utf8 COLLATE utf8_general_ci (33)
        Length: 3072
        Type: FIELD_TYPE_VAR_STRING (253)
        Flags: 0x0000
        Decimals: 0
         */
        $f2 = new MySQLField();
        $f2->catalog = "def";
        $f2->database = "";
        $f2->table = "session_variables";
        $f2->originalTable = "session_variables";
        $f2->name = "Value";
        $f2->originalName = "Value";
        $f2->charset = 0x21;
        $f2->length = 3072;
        $f2->type = MySQLField::FIELD_TYPE_VAR_STRING;
        $f2->flags = 0x0001; // not null
        $f2->decimals = 0;

        $fields = [$f1, $f2];
        $values = [
            ["Variable_name" => "lower_case_table_names", "Value" => 2],
        ];

        $connection->writeResultSet($fields, $values);
    } else if ($sql === "show variables like 'profiling'") {
        // ...
    } else if ($sql === "select 1") {
        $f1 = new MySQLField();
        $f1->catalog = "def";
        $f1->database = "";
        $f1->table = "session_variables";
        $f1->originalTable = "session_variables";
        $f1->name = "Variable_name";
        $f1->originalName = "Variable_name";
        $f1->length = 192;
        $f1->type = MySQLField::FIELD_TYPE_VAR_STRING;
        $f1->flags = 0x0001; // not null


        $f2 = new MySQLField();
        $f2->catalog = "def";
        $f2->database = "";
        $f2->table = "session_variables";
        $f2->originalTable = "session_variables";
        $f2->name = "Value";
        $f2->originalName = "Value";
        $f2->length = 3072;
        $f2->type = MySQLField::FIELD_TYPE_VAR_STRING;
        $f2->flags = 0x0001; // not null

        $fields = [$f1, $f2];
        $values = [
            ["Variable_name" => "lower_case_table_names", "Value" => 2],
        ];

        $connection->writeResultSet($fields, $values);
    } else {

    }
});
$mysqlServer->start();

exit;



$conf = [];
if (isset($argv[1])) {
    $conf["port"] = $argv[1];
} else {
    $conf["port"] = 3307;
}


class TimerFlag
{
    public $flag;

    private $isRun;
    private $min;
    private $max;

    public function __construct($min, $max, $init = true)
    {
        $this->min = $min;
        $this->max = $max;
        $this->flag = boolval($init);
        $this->isRun = false;
    }

    public function run()
    {
        if ($this->isRun === false) {
            $this->delaySwitch();
            $this->isRun = true;
        }
    }

    private function delaySwitch()
    {
        $delay = mt_rand($this->min, $this->max);
        swoole_timer_after($delay, function() {
            $this->flag = !$this->flag;
            $this->delaySwitch();
        });
    }
}




// 一段时间 mysql 协议登录阶段直接 被server close
// 一段时间 2000ms后 server端关闭连接
function test_close($conf)
{
    $timerFlag = new TimerFlag(1000, 5000, true);

    $mysqlServer = new \Minimalism\FakeServer\MySQL\FakeMySQLServer($conf);
    $mysqlServer->on("login", function(MySQLConnection $conn, $vars) use($timerFlag) {
        $timerFlag->run();

        if ($timerFlag->flag) {
            // 连接成功，2000ms 后关闭
            swoole_timer_after(2000, function() use($conn) {
                $conn->close();
            });

            $conn->responseOK();
//            return true;
        } else {
            // mysql 握手阶段直接关闭
            $conn->close();
            $conn->responseERR();
//            return false;
        }
    });

    $mysqlServer->start();
}

// mysql 协议 握手阶段 hold住5000ms
function test_timeout($conf, $delay = 5000)
{
    $timerFlag = new TimerFlag(1000, 5000, true);

    $mysqlServer = new \Minimalism\FakeServer\MySQL\FakeMySQLServer($conf);
    $mysqlServer->on("login", function(MySQLConnection $conn, $vars) use($delay, $timerFlag) {
        $timerFlag->run();

        // flag 背后有定时器间断性改变
        if ($timerFlag->flag) {
            $conn->responseOK();
        } else {
            swoole_timer_after($delay, function() use($conn) {
                $conn->responseOK();
            });
        }

        swoole_timer_after(2000, function() use($conn) {
            $conn->close();
        });
    });

    $mysqlServer->start();
}


//test_close($conf);

test_timeout($conf);
