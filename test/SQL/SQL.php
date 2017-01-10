<?php

namespace Minimalism\Test\SQL;



use Minimalism\SQL\Sql;

require __DIR__ . "/../../vendor/autoload.php";


/////////////////////////////////////////////////////////////////////////
// 一个简单的例子
/*
$dsn = "";
$pdo = new \PDO($dsn, "", "");
$sql = 'SELECT company_id,`name` FROM `t_company` WHERE `name` LIKE ' . SQL::_($bindArray, "name", "沙县%");
$stmt = $pdo->prepare($sql);
// $stmt = SQL::bindValues($stmt, $bindArray);
$stmt->execute($bindArray);
$ret = $stmt->fetchAll(\PDO::FETCH_ASSOC);
print_r($ret);
//*/
////////////////////////////////////////////////////////////////////////



Sql::_($bindArray, "name", "xiaofeng");
Sql::_($bindArray, "age", 26);
assert($bindArray === array (
        ':name' => 'xiaofeng',
        ':age' => 26,
    ));
unset($bindArray);

/** @noinspection PhpUndefinedVariableInspection */
Sql::_($bindArray, ":name", "xiaofeng");
Sql::_($bindArray, ":age", 26);
assert($bindArray === array (
        ':name' => 'xiaofeng',
        ':age' => 26,
    ));
unset($bindArray);

/** @noinspection PhpUndefinedVariableInspection */
Sql::_($bindArray, "info.field1", 1);
Sql::_($bindArray, "info.field2", 2);
assert($bindArray === array (
        ':info_field1' => 1,
        ':info_field2' => 2,
    ));
unset($bindArray);

/** @noinspection PhpUndefinedVariableInspection */
Sql::_($bindArray, "info.field3", 3);
Sql::_($bindArray, "info.field3", 3);
Sql::_($bindArray, "info.field3", 3);
assert($bindArray === array (
        ':info_field3' => 3,
        ':info_field3_' => 3,
        ':info_field3__' => 3,
    ));
unset($bindArray);

/** @noinspection PhpUndefinedVariableInspection */
Sql::_($bindArray, [
    "field1" => "f1",
    "field2" =>  2,
    ":field3"=> true,
]);
assert($bindArray === array (
        ':field1' => 'f1',
        ':field2' => 2,
        ':field3' => true,
    ));
unset($bindArray);

assert(Sql::select([]) === " * ");
assert(Sql::select(["field1", "field2", "field3"]) === " field1, field2, field3 ");

assert(Sql::insert($bindArray, [
        'name' => 'xiaofeng',
        'age' => 26,
    ]) === " (name, age) VALUES (:name, :age) ");
assert($bindArray === array (
        ':name' => 'xiaofeng',
        ':age' => 26,
    ));
unset($bindArray);

assert(Sql::insert($bindArray, [
        'name' => 'xiaofeng',
        'age' => 26,
    ], '?') === " (name, age) VALUES (?, ?) ");
assert($bindArray === ['xiaofeng', 26,]);
unset($bindArray);

assert(Sql::batchInsert($bindArray, [[
        "id" => 1,
        "name" => "n1"
    ], [
        "id" => 2,
        "name" => "n2"
    ]]) === " (id, name) VALUES (:id_0_0, :name_0_1),(:id_1_0, :name_1_1) ");
assert($bindArray === array (
        ':id_0_0' => 1,
        ':name_0_1' => 'n1',
        ':id_1_0' => 2,
        ':name_1_1' => 'n2',
    ));
unset($bindArray);

assert(Sql::batchInsert($bindArray, [[
        "id" => 1,
        "name" => "n1"
    ], [
        "id" => 2,
        "name" => "n2"
    ]], '?') === " (id, name) VALUES (?, ?), (?, ?) ");
assert($bindArray === array (1, 'n1', 2, 'n2',));
unset($bindArray);

assert(Sql::update($bindArray, [
        "id" => 1,
        "name" => "n1"
    ], ":") === " id = :id, name = :name ");
assert($bindArray === array (
        ':id' => 1,
        ':name' => 'n1',
    ));
unset($bindArray);

assert(Sql::update($bindArray, [
        "id" => 1,
        "name" => "n1"
    ], "?") === " id = ?, name = ? ");
assert($bindArray === array (1, 'n1'));
unset($bindArray);

assert(Sql::like($bindArray, "`name`", "xiaofeng%") === " `name`  LIKE :name ");
assert($bindArray === array (':name' => 'xiaofeng%',));
unset($bindArray);

assert(Sql::like($bindArray, "`name`", "xiaofeng%", "?") === " `name`  LIKE ? ");
assert($bindArray === array ('xiaofeng%',));
unset($bindArray);

assert(Sql::notLike($bindArray, "`name`", "xiaofeng%") === " `name` NOT LIKE :name ");
assert($bindArray === array (':name' => 'xiaofeng%',));
unset($bindArray);

assert(Sql::notLike($bindArray, "`name`", "xiaofeng%", "?") === " `name` NOT LIKE ? ");
assert($bindArray === array ('xiaofeng%',));
unset($bindArray);


assert(Sql::in($bindArray, "id", range(1, 3)) === " id  IN (:id_0, :id_1, :id_2) ");
assert($bindArray === array (
        ':id_0' => 1,
        ':id_1' => 2,
        ':id_2' => 3,
    ));
unset($bindArray);


assert(Sql::notIn($bindArray, "id", range(1, 3)) === " id NOT IN (:id_0, :id_1, :id_2) ");
assert($bindArray === array (
        ':id_0' => 1,
        ':id_1' => 2,
        ':id_2' => 3,
    ));
unset($bindArray);

assert(Sql::in($bindArray, "id", range(1, 3), "?") === " id  IN (?, ?, ?) ");
assert($bindArray === array (1, 2, 3,));
unset($bindArray);

assert(Sql::notIn($bindArray, "id", range(1, 3), "?") === " id NOT IN (?, ?, ?) ");
assert($bindArray === array (1, 2, 3,));
unset($bindArray);

assert(Sql::orderBy(["id" => "asc", "rank" => "desc"], ["id", "rank"]) === " id ASC , rank DESC ");

$where = [
    ['name', 'like', 'xiao%'],
    'or' => [
        ['age', '<', 10],
        ['age', '>', 20],
    ],
    ['sex', 'not in', [0, 2]],
];
assert(Sql::where($bindArray, $where) === "  name  LIKE :name  AND ( age < :age OR age > :age_ ) AND  sex NOT IN (:sex_0, :sex_1)  ");
assert($bindArray === array (
        ':name' => 'xiao%',
        ':age' => 10,
        ':age_' => 20,
        ':sex_0' => 0,
        ':sex_1' => 2,
    ));
unset($bindArray);

assert(Sql::where($bindArray, $where, "or") === "  name  LIKE :name  OR ( age < :age OR age > :age_ ) OR  sex NOT IN (:sex_0, :sex_1)  ");
unset($bindArray);

assert(Sql::where($bindArray, $where, "or", "?") === "  name  LIKE ?  OR ( age < ? OR age > ? ) OR  sex NOT IN (?, ?)  ");
assert($bindArray === array ('xiao%', 10, 20, 0, 2,));
unset($bindArray);

$where = [
    'or' => [
        ['age', '<', 10],
        ['age', '>', 20],
    ],
];
assert(Sql::where($bindArray, $where) === " ( age < :age OR age > :age_ ) ");
unset($bindArray);