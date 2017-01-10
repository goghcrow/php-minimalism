<?php

namespace Minimalism\SQL\Builder;



use Minimalism\SQL\Sql;

abstract class SQLBuilderBase implements SQLBuilder
{
    protected $whiteList; /* column => column */
    protected $bindArray;
    protected $table;

    protected $where;
    protected $limit;

    protected function __construct($table) {
        $this->table = $table;
    }

    /**
     * setTableFields and column white list
     * @param array $columns
     * @return $this
     * TODO 针对设置的字段白名单在拼接函数中进行过滤
     */
    public function setColumnWhiteList(array $columns) {
        $this->whiteList = array_map("strtolower", $columns);
        $this->whiteList = array_combine($this->whiteList, $this->whiteList);
        return $this;
    }

    public static function from($table) {
        return new static($table);
    }

    abstract  public function getSQL();

    public function getBound() {
        return $this->bindArray;
    }

    protected function assertEmpty($var, $what) {
        if (!empty($var)) {
            // https://www.quora.com/Is-setted-a-wrong-word
            throw new SQLBuilderException("$what has been set");
        }
    }

    protected function assertNotEmpty($var, $what) {
        if (empty($var)) {
            throw new SQLBuilderException("$what need to be set");
        }
    }

    /**
     * @param array $columns
     * @return array
     * 按白名单清洗列数据
     */
    protected function sanitizeColumns(array $columns) {
        if ($this->whiteList && $columns) {
            return array_intersect(array_map("strtolower", $columns), $this->whiteList);
        }
        return $columns;
    }

    protected function checkColumns(array $columns) {
        if (empty($this->whiteList) || empty($columns)) {
            return;
        }

        foreach ($columns as $column) {
            if (!isset($this->whiteList[strtolower($column)])) {
                throw new SQLBuilderException("`$column` is not in column whitelist");
            }
        }
    }

    /**
     * @param array $cond
     * @throws SQLBuilderException
     *
     * 检查where条件的列白名单
     */
    protected function checkWhere(array $cond) {
        if (empty($this->whiteList)) {
            return;
        }

        foreach ($cond as $key => $value) {
            $key = strtolower($key);
            if ($key === "and" || $key === "or") {
                $this->checkWhere($value);
            } else {
                $column = isset($value[0]) ? $value[0] : "bad where condition";
                if (!isset($this->whiteList[strtolower($column)])) {
                    throw new SQLBuilderException("`$column` is not in column whitelist");
                }
            }
        }
    }

    public function where(array $cond = [], $relation = "AND") {
        $this->assertEmpty($this->where, __FUNCTION__);
        $this->checkWhere($cond);
        $this->where = Sql::where($this->bindArray, $cond, $relation);
        return $this;
    }

    public function limit($offset, $limit = null) {
        $this->limit = Sql::limit($offset, $limit);
        return $this;
    }
}