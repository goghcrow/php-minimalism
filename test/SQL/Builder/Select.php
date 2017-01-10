<?php

namespace Minimalism\Test\SQL\Builder;


use Exception;
use Minimalism\SQL\Builder\Select;

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


$where = [
    ["company_id", "in", [167,180,200,201,203,214,219]],
    "or" => [
        ['`name`', "not like", "沙县%"],
        ["type", "<>", 0],
    ]
];

/* @var $select Select */
$select = Select::from("t_company")
    ->select(["id", "company_id"])
    ->where($where)
    ->orderBy(["id" => "ASC", "company_id" => "DESC"])
    ->limit(100, 10);


/** @noinspection SqlDialectInspection */
/** @noinspection SqlNoDataSourceInspection */
$sql = "SELECT  id, company_id  FROM t_company WHERE   company_id  IN (:company_id_0, :company_id_1, :company_id_2, :company_id_3, :company_id_4, :company_id_5, :company_id_6)  AND (  `name` NOT LIKE :name  OR type <> :type )  ORDER BY  id ASC , company_id DESC  LIMIT 100, 10";

assert($select->getSQL() === $sql);
assert($select->getBound() === array (
        ':company_id_0' => 167,
        ':company_id_1' => 180,
        ':company_id_2' => 200,
        ':company_id_3' => 201,
        ':company_id_4' => 203,
        ':company_id_5' => 214,
        ':company_id_6' => 219,
        ':name' => '沙县%',
        ':type' => 0,
    ));



///////////////////////////////////////////////////////////////////////////////////////////

assert_exception(function() {
    $where = [
        ['name', 'like', 'xiao%'],
        'or' => [
            ['age', '<', 10],
            ['age', '>', 20],
        ],
        ['sex', 'not in', [0, 2]],
    ];
    Select::from("t_company")->setColumnWhiteList(["age", "name"])->where($where);
}, '`sex` is not in column whitelist');

///////////////////////////////////////////////////////////////////////////////////////////


assert_exception(function() {
    Select::from("t_company")->setColumnWhiteList(["age", "name"])->orderBy(["id" => "asc"]);
}, '`id` is not in column whitelist');

///////////////////////////////////////////////////////////////////////////////////////////




$where = [
    ['name', "not like", "沙县%"],
    ["type", "<>", 0],
];

try {
    Select::from("t_company")->setColumnWhiteList(["id"])->groupBy("invalid_column");
    assert(false);
} catch (Exception $ex) {
    assert($ex->getMessage() === '`invalid_column` is not in column whitelist');
}



/* @var $select Select */
$select = Select::from("t_company")
    ->setColumnWhiteList(['id', 'name', 'type', 'company_id'])
    ->select(["id", "company_id"])
    ->where($where)
    ->groupBy("company_id", "type")
    ->orderBy(["id" => "ASC", "company_id" => "DESC"])
    ->limit(100, 10);


/** @noinspection SqlDialectInspection */
/** @noinspection SqlNoDataSourceInspection */
$sql = "SELECT  id, company_id  FROM t_company WHERE   name NOT LIKE :name  AND type <> :type  GROUP BY company_id,type ORDER BY  id ASC , company_id DESC  LIMIT 100, 10";

assert($select->getSQL() === $sql);
assert($select->getBound() === array (
        ':name' => '沙县%',
        ':type' => 0,
    ));