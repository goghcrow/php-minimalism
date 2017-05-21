<?php

namespace Minimalism\FakeServer;

use Minimalism\FakeServer\MySQL\MySQLCommand;
use Minimalism\FakeServer\MySQL\MySQLConnection;
use Minimalism\FakeServer\MySQL\MySQLField;

require_once __DIR__ . "/../load.php";

//$conf = [];
//if (isset($argv[1])) {
//    $conf["port"] = $argv[1];
//} else {
//    $conf["port"] = 3307;
//}


$mysqlServer = new \Minimalism\FakeServer\MySQL\FakeMySQLServer(["port" => 7777]);

$mysqlServer->on("login", function(MySQLConnection $connection, array $data)  {
//    if ($connection->authorize()) {
        $connection->authorizeOK();
//    } else {
//        $connection->authorizeERR();
//    }
});

$mysqlServer->on("command", function(MySQLConnection $connection, $command, array $args) {
    echo $connection->getCommandName($command), "\n";
    var_dump($args);

    switch ($command) {
        case MySQLCommand::COM_SHUTDOWN:
            $connection->sendOK();
            break;

        default:
            $connection->sendErrorUnsupported($command);
    }
});


$mysqlServer->on("query", function(MySQLConnection $connection, $sql) {
    echo "sql: $sql\n";

    if (strtoupper(substr($sql, 0, 3)) === "USE") {
        $database = substr($sql, 3);
        $database = trim($database, " \t\n\r\0\x0B`;");
        var_dump($database);
        $connection->setDatabase($database);
        $connection->sendOK();
    } else if ($sql === "SHOW DATABASES") {
        $f = new MySQLField();
        $f->catalog = "def";
        $f->database = "information_schema";
        $f->table = "SCHEMATA";
        $f->originalTable = "SCHEMATA";
        $f->name = "Database";
        $f->originalName = "Database";
        $f->charset = 0x21;
        $f->length = 253;
        $f->type = MySQLField::FIELD_TYPE_VAR_STRING;
        $f->flags = 0x0001; // not null
        $f->decimals = 0;

        $fields = [$f];
        $values = [
            ["Database" => "D1"],
            ["Database" => "D2"],
            ["Database" => "D3"],
        ];

        $connection->sendResultSet($fields, $values);
    }  else if ($sql === "SHOW TABLES") {
        $f = new MySQLField();
        $f->catalog = "def";
        $f->database = "information_schema";
        $f->table = "TABLE_NAMES";
        $f->originalTable = "TABLE_NAMES";
        $f->name = "TABLE_NAME";
        $f->originalName = "TABLE_NAME";
        $f->charset = 0x21;
        $f->length = 192;
        $f->type = MySQLField::FIELD_TYPE_VAR_STRING;
        $f->flags = 0x0001; // not null
        $f->decimals = 0;

        $f2 = new MySQLField();
        $f2->catalog = "def";
        $f2->database = "information_schema";
        $f2->table = "TABLE_NAMES";
        $f2->originalTable = "TABLE_NAMES";
        $f2->name = "Table_type";
        $f2->originalName = "Table_type";
        $f2->charset = 0x21;
        $f2->length = 192;
        $f2->type = MySQLField::FIELD_TYPE_VAR_STRING;
        $f2->flags = 0x0001; // not null
        $f2->decimals = 0;

        $fields = [$f, $f2];
        $values = [
            ["TABLE_NAME" => "tableA", "Table_type" => "BASE TABLE"],
            ["TABLE_NAME" => "tableB", "Table_type" => "BASE TABLE"],
            ["TABLE_NAME" => "tableC", "Table_type" => "BASE TABLE"],
            ["TABLE_NAME" => "tableD", "Table_type" => "BASE TABLE"],
        ];

        $connection->sendResultSet($fields, $values);
    }  else if ($sql === "show variables like 'lower_case_table_names'") {
        $f = new MySQLField();
        $f->catalog = "def";
        $f->database = "";
        $f->table = "session_variables";
        $f->originalTable = "session_variables";
        $f->name = "Variable_name";
        $f->originalName = "Variable_name";
        $f->charset = 0x21;
        $f->length = 192;
        $f->type = MySQLField::FIELD_TYPE_VAR_STRING;
        $f->flags = 0x0001; // not null
        $f->decimals = 0;

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

        $fields = [$f, $f2];
        $values = [
            ["Variable_name" => "profiling", "Value" => "OFF"],
        ];

        $connection->sendResultSet($fields, $values);
    } else if ($sql === "show variables like 'profiling'") {
        $f = new MySQLField();
        $f->catalog = "def";
        $f->database = "";
        $f->table = "session_variables";
        $f->originalTable = "session_variables";
        $f->name = "Variable_name";
        $f->originalName = "Variable_name";
        $f->charset = 0x21;
        $f->length = 192;
        $f->type = MySQLField::FIELD_TYPE_VAR_STRING;
        $f->flags = 0x0001; // not null
        $f->decimals = 0;

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

        $fields = [$f, $f2];
        $values = [
            ["Variable_name" => "lower_case_table_names", "Value" => 2],
        ];

        $connection->sendResultSet($fields, $values);
    } else if (strtoupper(substr($sql, 0, 6)) === "SELECT") {
        $fields = [];
        $values = [];
        for ($i = 0; $i < 10; $i++) {
            $f = new MySQLField();
            $f->catalog = "def";
            $f->database = "test_db";
            $f->table = "test_table";
            $f->originalTable = "test_table";
            $f->name = "test_field$i";
            $f->originalName = "test_field$i";
            $f->length = 192;
            $f->type = MySQLField::FIELD_TYPE_VAR_STRING;
            $f->flags = 0x0001; // not null
            $fields[] = $f;
        }

        for ($j = 0; $j < 100; $j++) {
            $row = [];
            for ($i = 0; $i < 10; $i++) {
                $row["test_field$i"] = $i;
            }
            $values[] = $row;
        }

        $connection->sendResultSet($fields, $values);
    } else {
        $connection->sendError("Unsupported sql: $sql");
    }
});
$mysqlServer->start();