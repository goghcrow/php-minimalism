<?php

namespace Minimalism\Test\SQL\Builder;


use Minimalism\SQL\Builder\Insert;

require __DIR__ . "/../../../vendor/autoload.php";


if (!function_exists("assert_exception")) {
    function assert_exception(\Closure $func, $exMsg) {
        try {
            $func();
            assert(false);
        } catch (\Exception $ex) {
            assert($ex->getMessage() === $exMsg, $ex->getMessage() . "\t");
        }
    }
}


assert_exception(function() {
    Insert::from("t_company")->setColumnWhiteList(["a", "b"])->value([
        "a" => 1,
        "b" => 2,
        "c" => 3,
    ]);
}, "`c` is not in column whitelist");


assert_exception(function() {
    Insert::from("t_company")->setColumnWhiteList(["company_id", "name"])->values([[
        "company_id" => 1,
        "name"      => "xxx",
        "type"      => 0,
    ], [
        "company_id" => 2,
        "name"      => "yyy",
        "type"      => 0,
    ]]);
}, "`type` is not in column whitelist");

assert_exception(function() {
    Insert::from("t_company")->setColumnWhiteList(["name"])->update([
        "name" => "CONCAT(VALUES(name), '_xxx')",
        "type" => 1,
    ]);
}, "`type` is not in column whitelist");

/* @var $insert Insert */
$insert = Insert::from("t_company")->replace()->value([
    "company_id" => 1,
    "name"      => "xxx",
    "type"      => 0,
]);

/** @noinspection SqlDialectInspection */
/** @noinspection SqlNoDataSourceInspection */
$sql = "REPLACE INTO t_company  (company_id, name, type) VALUES (:company_id, :name, :type) ";

assert($insert->getSQL() === $sql);
assert($insert->getBound() === array (
        ':company_id' => 1,
        ':name' => 'xxx',
        ':type' => 0,
    ));


//////////////////////////////////////////////////////////////////////////////////////////////


/* @var $insert Insert */
$insert = Insert::from("t_company")->values([[
    "company_id" => 1,
    "name"      => "xxx",
    "type"      => 0,
], [
    "company_id" => 2,
    "name"      => "yyy",
    "type"      => 0,
], [
    "company_id" => 3,
    "name"      => "zzz",
    "type"      => 0,
]]);


/** @noinspection SqlDialectInspection */
/** @noinspection SqlNoDataSourceInspection */
$sql = "INSERT INTO t_company  (company_id, name, type) VALUES (:company_id_0_0, :name_0_1, :type_0_2),(:company_id_1_0, :name_1_1, :type_1_2),(:company_id_2_0, :name_2_1, :type_2_2) ";

assert($insert->getSQL() === $sql);
assert($insert->getBound() === array (
        ':company_id_0_0' => 1,
        ':name_0_1' => 'xxx',
        ':type_0_2' => 0,
        ':company_id_1_0' => 2,
        ':name_1_1' => 'yyy',
        ':type_1_2' => 0,
        ':company_id_2_0' => 3,
        ':name_2_1' => 'zzz',
        ':type_2_2' => 0,
    ));


//////////////////////////////////////////////////////////////////////////////////////////////


/* @var $insert Insert */
$insert = Insert::from("t_company")->value([
    "company_id" => 3,
    "name"      => "zzz",
    "type"      => 0,
])->update([
   "name" => "CONCAT(VALUES(name), '_xxx')",
    "type" => 1,
]);

/** @noinspection SqlDialectInspection */
/** @noinspection SqlNoDataSourceInspection */
$sql = "INSERT INTO t_company  (company_id, name, type) VALUES (:company_id, :name, :type)  ON DUPLICATE KEY UPDATE  type = :type_  name = CONCAT(VALUES(name), '_xxx') ";

assert($insert->getSQL() === $sql);
assert($insert->getBound() === array (
        ':company_id' => 3,
        ':name' => 'zzz',
        ':type' => 0,
        ':type_' => 1,
    ));
