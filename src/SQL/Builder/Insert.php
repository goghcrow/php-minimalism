<?php

namespace Minimalism\SQL\Builder;


use Minimalism\SQL\Sql;

class Insert extends SQLBuilderBase
{
    protected $value;
    protected $replace;
    protected $dupSets;

    public function replace() {
        $this->replace = true;
        return $this;
    }

    public function value(array $row) {
        $this->assertEmpty($this->value, __FUNCTION__);
        $this->checkColumns(array_keys($row));
        $this->value = Sql::insert($this->bindArray, $row);
        return $this;
    }

    public function values(array $rows) {
        $this->assertEmpty($this->value, __FUNCTION__);
        foreach ($rows as $row) {
            $this->checkColumns(array_keys($row));
        }
        $this->value = Sql::batchInsert($this->bindArray, $rows);
        return $this;
    }

    /**
     * ON DUPLICATE KEY UPDATE
     * @param array $row
     * @return $this
     * @throws SQLBuilderException
     */
    public function update(array $row) {
        $this->assertEmpty($this->dupSets, "ON DUPLICATE KEY UPDATE");
        $this->checkColumns(array_keys($row));
        $bindArray = $unBindArray = [];
        foreach ($row as $column => $val) {
            // !!! 这里有安全问题,没想到简单的办法处理 a = CONCAT(VALUES(columnX), "unsafe string")
            // 也没有办法
            if (preg_match('/VALUES(.+)/i', $val)) {
                $unBindArray[$column] = $val;
            } else {
                $bindArray[$column] = $val;
            }
        }
        $this->dupSets = Sql::update($this->bindArray, $bindArray);
        $this->dupSets .= Sql::updateUnsafe($unBindArray);
        return $this;
    }

    public function getSQL() {
        $this->assertNotEmpty($this->value, "value");
        /** @noinspection SqlNoDataSourceInspection */
        $sql = $this->replace ? "REPLACE" : "INSERT";
        $sql.= " INTO {$this->table} " . $this->value;
        if ($this->dupSets) {
            $sql .= " ON DUPLICATE KEY UPDATE {$this->dupSets}";
        }
        return $sql;
    }
}