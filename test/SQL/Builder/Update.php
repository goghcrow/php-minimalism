<?php

namespace Minimalism\Test\SQL\Builder;



use Minimalism\SQL\Builder\Update;

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
    Update::from("t_company")->setColumnWhiteList(["`name`"])
        ->set([
            "`name`" => "hello",
            "type"  => 2,
        ]);
}, "`type` is not in column whitelist");


$where = [
    ["company_id", "in", [167,180,200,201,203,214,219]],
    "or" => [
        ['`name`', "not like", "沙县%"],
        ["type", "<>", 0],
    ]
];

/* @var $update Update */
$update = Update::from("t_company")
    ->set([
        "`name`" => "hello",
        "type"  => 2,
    ])
    ->where($where)
    ->limit(100);


/** @noinspection SqlDialectInspection */
/** @noinspection SqlNoDataSourceInspection */
$sql = "UPDATE t_company SET  `name` = :name, type = :type  WHERE   company_id  IN (:company_id_0, :company_id_1, :company_id_2, :company_id_3, :company_id_4, :company_id_5, :company_id_6)  AND (  `name` NOT LIKE :name_  OR type <> :type_ ) LIMIT 100";

assert($update->getSQL() === $sql);
assert($update->getBound() === array (
        ':name' => 'hello',
        ':type' => 2,
        ':company_id_0' => 167,
        ':company_id_1' => 180,
        ':company_id_2' => 200,
        ':company_id_3' => 201,
        ':company_id_4' => 203,
        ':company_id_5' => 214,
        ':company_id_6' => 219,
        ':name_' => '沙县%',
        ':type_' => 0,
    ));