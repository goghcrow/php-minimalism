<?php

namespace Minimalism\Test\SQL\Builder;



use Minimalism\SQL\Builder\Delete;

require __DIR__ . "/../../../vendor/autoload.php";


$where = [
    ["company_id", "in", [167,180,200,201,203,214,219]],
    "or" => [
        ['`name`', "not like", "沙县%"],
        ["type", "<>", 0],
    ]
];

/* @var $delete Delete */
$delete = Delete::from("t_company")
    ->where($where)
    ->limit(100);

/** @noinspection SqlDialectInspection */
/** @noinspection SqlNoDataSourceInspection */
$sql = "DELETE FROM t_company WHERE   company_id  IN (:company_id_0, :company_id_1, :company_id_2, :company_id_3, :company_id_4, :company_id_5, :company_id_6)  AND (  `name` NOT LIKE :name  OR type <> :type ) LIMIT 100";

assert($delete->getSQL() === $sql);
assert($delete->getBound() === array (
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