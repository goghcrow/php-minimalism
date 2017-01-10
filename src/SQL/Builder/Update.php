<?php

namespace Minimalism\SQL\Builder;


use Minimalism\SQL\Sql;

class Update extends SQLBuilderBase
{
    protected $sets;

    public function set($row) {
        $this->assertEmpty($this->sets, __FUNCTION__);
        $this->checkColumns(array_keys($row));
        $this->sets = Sql::update($this->bindArray, $row);
        return $this;
    }

    public function getSQL() {
        $this->assertNotEmpty($this->sets, "sets");
        /** @noinspection SqlNoDataSourceInspection */
        $sql = "UPDATE {$this->table} SET {$this->sets} ";
        if($this->where) {
            $sql .= "WHERE {$this->where}";
        }
        if ($this->limit) {
            $sql .= "LIMIT {$this->limit}";
        }
        return $sql;
    }
}