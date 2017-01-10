<?php

namespace Minimalism\SQL\Builder;


use Minimalism\SQL\Sql;

/**
 * Class Select
 * @package Minimalism\SQL\Builder
 *
 * http://dev.mysql.com/doc/refman/5.7/en/select.html
 */
class Select extends SQLBuilderBase
{
    protected $select;
    protected $orderBy;
    protected $groupBy;

    public function select($columns = []) {
        $this->select = Sql::select($columns);
        return $this;
    }

    public function groupBy(/* ...$args */) {
        $columns = func_get_args();
        if ($columns) {
            // $columns = $this->sanitizeColumns($columns);
            $this->checkColumns($columns);
            $this->groupBy = implode(",", $columns);
        }
        return $this;
    }

    public function orderBy(array $orderByPairs) {
        $this->checkColumns(array_keys($orderByPairs));
        $this->orderBy = Sql::orderBy($orderByPairs); // 不使用orderBy的检测
        return $this;
    }

    public function getSQL() {
        if (empty($this->select)) {
            $this->select = " * ";
        }
        /** @noinspection SqlNoDataSourceInspection */
        $sql = "SELECT {$this->select} FROM {$this->table} ";
        if($this->where) {
            $sql .= "WHERE {$this->where} ";
        }
        if ($this->groupBy) {
            $sql .= "GROUP BY {$this->groupBy} ";
        }
        if($this->orderBy) {
            $sql .= "ORDER BY {$this->orderBy} ";
        }
        if ($this->limit) {
            $sql .= "LIMIT {$this->limit}";
        }
        return $sql;
    }
}