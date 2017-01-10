<?php

namespace Minimalism\SQL\Builder;


class Delete extends SQLBuilderBase
{
    public function getSQL() {
        /** @noinspection SqlNoDataSourceInspection */
        $sql = $sql = "DELETE FROM {$this->table} ";
        if($this->where) {
            $sql .= "WHERE $this->where";
        }
        if ($this->limit) {
            $sql .= "LIMIT {$this->limit}";
        }
        return $sql;
    }
}