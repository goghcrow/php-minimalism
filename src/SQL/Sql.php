<?php

namespace Minimalism\SQL;


use InvalidArgumentException;

/**
 * Class Sql
 * @package Minimalism\SQL
 *
 * 手动拼接预编译sql语句的辅助类
 */
class Sql
{
    /**
     * @param $var
     * @return int
     * is_* faster than gettype
     */
    public static function pdoType($var) {
        if($var === null) return \PDO::PARAM_NULL;
        if(is_bool($var)) return \PDO::PARAM_BOOL;
        if(is_int($var)) return \PDO::PARAM_INT;
        if(is_resource($var)) return \PDO::PARAM_LOB;
        return \PDO::PARAM_STR; // others type => pdostr
    }

    /**
     * bindValues
     * @param \PDOStatement $stmt
     * @param array $keyValues
     * @return \PDOStatement
     */
    public static function bindValues(\PDOStatement $stmt, array $keyValues) {
        static::_assertNotEmpty($keyValues, __FUNCTION__ . " keyValues");
        foreach ($keyValues as $key => $value) {
            $stmt->bindValue($key, $value, Sql::pdoType($value));
        }
        return $stmt;
    }

    private static function getBindKey(&$bindArray, $bindKey, $bindValue) {
        // bindKey 不支持table.column, 替换成 table_column
        $bindKey = str_replace(['.', '`'], ['_', ''], trim($bindKey));
        // :name 占位符
        if($bindKey[0] !== ":") {
            $bindKey = ":$bindKey";
        }
        // init input parameter
        if($bindArray == null) {
            $bindArray = [];
        }
        // prevent repeating key
        if(isset($bindArray[$bindKey])) {
            $bindKey = static::getBindKey($bindArray, "{$bindKey}_", $bindValue);
        } else {
            $bindArray[$bindKey] = $bindValue;
        }
        return $bindKey;
    }

    private static function getBindKeys(&$bindArray, array $keyValues) {
        $bindKeys = [];
        foreach($keyValues as $key => $value) {
            $bindKeys[] = static::getBindKey($bindArray, $key, $value);
        }
        return $bindKeys;
    }

    /**
     * getBindKey[s]
     * @param array $bindArray
     * @param string|array $bindKey
     * @param mixed $bindValue
     * @return string|array
     * @throws \InvalidArgumentException
     */
    public static function _(&$bindArray, $bindKey, $bindValue = null) {
        if(is_array($bindKey)) {
            return static::getBindKeys($bindArray, $bindKey);
        } else {
            return static::getBindKey($bindArray, $bindKey, $bindValue);
        }
    }

    /**
     * select
     * @param array $columns
     * @return string
     */
    public static function select(array $columns) {
        if(empty($columns)) {
            return " * ";
        }
        return " " . implode(", ", array_values($columns)) . " ";
    }

    private static function _insertColon(&$bindArray, array $keyValues) {
        $columns = implode(", ", array_keys($keyValues));
        $values = implode(", ", static::getBindKeys($bindArray, $keyValues));
        return " ($columns) VALUES ($values) ";
    }

    private static function _insertQ(&$bindArray, array $keyValues) {
        $columns = implode(", ", array_keys($keyValues));
        $values = implode(", ", array_fill(0, count($keyValues), '?'));
        $bindArray = array_merge($bindArray ?: [], array_values($keyValues));
        return " ($columns) VALUES ($values) ";
    }

    /**
     * insert
     * @param array $bindArray
     * @param array $row
     * @param string $placeHolder
     * @return string
     * @throws InvalidArgumentException
     */
    public static function insert(&$bindArray, array $row, $placeHolder = ":") {
        static::_assertNotEmpty($row, __FUNCTION__ . " row");
        if($placeHolder === ":") return static::_insertColon($bindArray, $row);
        if($placeHolder === "?") return static::_insertQ($bindArray, $row);
        throw new InvalidArgumentException("placeHolder should be : or ?");
    }

    private static function _batchInsertColon(&$bindArray, array $rows) {
        $columns = implode(", ", array_keys($rows[0]));
        $rowsArr = [];
        foreach($rows as $i => $pairs) {
            $j = 0;
            $rowArr = [];
            foreach($pairs as $column => $value) {
                $rowArr[] = static::getBindKey($bindArray, "{$column}_{$i}_{$j}", $value);
                $j++;
            }
            $rowsArr[] = '(' . implode(", ", $rowArr)  . ')';
        }
        $values = implode(',', $rowsArr);
        return " ($columns) VALUES $values ";
    }

    private static function _batchInsertQ(&$bindArray, array $rows) {
        $columns = implode(", ", array_keys($rows[0]));
        foreach($rows as $row) {
            foreach(array_values($row) as $value) {
                $bindArray[] = $value;
            }
        }
        $single = implode(", ", array_fill(0, count($rows[0]), '?'));
        $values = implode(", ", array_fill(0, count($rows), "($single)"));
        return " ($columns) VALUES $values ";
    }

    /**
     * batchInsert
     * @param array $bindArray
     * @param array $rows
     * @param string $placeHolder
     * @return string
     * @throws InvalidArgumentException
     */
    public static function batchInsert(&$bindArray, array $rows, $placeHolder = ":") {
        static::_assertNotEmpty($rows, __FUNCTION__ . " rows");
        if($placeHolder === ":") return static::_batchInsertColon($bindArray, $rows);
        if($placeHolder === "?") return static::_batchInsertQ($bindArray, $rows);
        throw new InvalidArgumentException("placeHolder should be : or ?");
    }

    private static function _updateColon(&$bindArray, array $keyValues) {
        $sets = array_map(function($key, $bindKey) {
            return "$key = $bindKey";
        }, array_keys($keyValues), static::getBindKeys($bindArray, $keyValues));
        return " " . implode(", ", $sets) . " ";
    }

    private static function _updateQ(&$bindArray, array $keyValues) {
        $bindArray = array_merge($bindArray ?: [], array_values($keyValues));
        $sets = array_map(function($f) { return "$f = ?";}, array_keys($keyValues));
        return " " . implode(", ", $sets) . " ";
    }

    /**
     * @param array $bindArray
     * @param array $keyValues
     * @param string $placeHolder
     * @return string
     * @throws InvalidArgumentException
     *
     * !!! 注意keyValues的value会被全部转换成绑定变量
     * 所以 像 LAST_INSERT_ID(id) 等等 mysql的函数 都无法使用~
     */
    public static function update(&$bindArray, array $keyValues, $placeHolder = ":") {
        static::_assertNotEmpty($keyValues, __FUNCTION__ . " keyValues");
        if($placeHolder === ":") return static::_updateColon($bindArray, $keyValues);
        if($placeHolder === "?") return static::_updateQ($bindArray, $keyValues);
        throw new InvalidArgumentException("placeHolder should be : or ?");
    }

    /**
     * @param array $keyValues
     * @return string
     */
    public static function updateUnsafe(array $keyValues) {
        $ret = [];
        foreach ($keyValues as $key => $value) {
            $ret[] = "$key = $value";
        }
        return " " . implode(", ", $ret) . " ";
    }

    private static function _likeColon(&$bindArray, $column, $value, $not = false) {
        $not = $not ? "NOT" : "";
        return " $column $not LIKE " . static::getBindKey($bindArray, $column, $value) . " ";
    }

    private static function _likeQ(&$bindArray, $column, $value, $not = false) {
        $not = $not ? "NOT" : "";
        $bindArray[] = $value;
        return " $column $not LIKE ? ";
    }

    /**
     * like
     * @param array $bindArray
     * @param string $column
     * @param string $value
     * @param string $placeHolder
     * @return string
     */
    public static function like(&$bindArray, $column, $value, $placeHolder = ':') {
        if($placeHolder === ":") return static::_likeColon($bindArray, $column, $value, false);
        if($placeHolder === "?") return static::_likeQ($bindArray, $column, $value, false);
        throw new InvalidArgumentException("placeHolder should be : or ?");
    }

    /**
     * not like
     * @param array $bindArray
     * @param string $column
     * @param string $value
     * @param string $placeHolder
     * @return string
     */
    public static function notLike(&$bindArray, $column, $value, $placeHolder = ':') {
        if($placeHolder === ":") return static::_likeColon($bindArray, $column, $value, true);
        if($placeHolder === "?") return static::_likeQ($bindArray, $column, $value, true);
        throw new InvalidArgumentException("placeHolder should be : or ?");
    }

    private static function _inColon(&$bindArray, $column, array $array, $not = false) {
        if (count($array) === 1) {
            return $column . ($not ? " <> " : " = ") . static::getBindKey($bindArray, $column, $array[0]);
        }
        $inArr = [];
        foreach($array as $k => $v) {
            $inArr[] = static::getBindKey($bindArray, "{$column}_{$k}", $v);
        }
        $valuesStr = implode(', ', $inArr);
        $not = $not ? "NOT" : '';
        return " $column $not IN ($valuesStr) ";
    }

    private static function _inQ(&$bindArray, $column, array $array, $not = false) {
        $bindArray = array_merge($bindArray ?: [], $array);
        if (count($array) === 1) {
            return $column . ($not ? " <> " : " = ") . "?";
        }
        $valuesStr = implode(", ", array_fill(0, count($array), '?'));
        $not = $not ? "NOT" : '';
        return " $column $not IN ($valuesStr) ";
    }

    /**
     * in
     * @param array $bindArray
     * @param string $column
     * @param array $array
     * @param string $placeHolder
     * @return string
     */
    public static function in(&$bindArray, $column, array $array, $placeHolder = ':') {
        static::_assertNotEmpty($array, __FUNCTION__ . " array");
        if($placeHolder === ":") return static::_inColon($bindArray, $column, $array, false);
        if($placeHolder === "?") return static::_inQ($bindArray, $column, $array, false);
        throw new InvalidArgumentException("placeHolder should be : or ?");
    }

    /**
     * not in
     * @param array $bindArray
     * @param string $column
     * @param array $array
     * @param string $placeHolder
     * @return string
     */
    public static function notIn(&$bindArray, $column, array $array, $placeHolder = ':') {
        static::_assertNotEmpty($array, __FUNCTION__ . " array");
        if($placeHolder === ":") return static::_inColon($bindArray, $column, $array, true);
        if($placeHolder === "?") return static::_inQ($bindArray, $column, $array, true);
        throw new \InvalidArgumentException("placeHolder should be : or ?");
    }

    /**
     * where
     * @param $bindArray
     * @param array $cond
     * @param string $relation
     * @param string $placeHolder
     * @return string
     */
    public static function where(&$bindArray, array $cond = [], $relation = "AND", $placeHolder = ':')
    {
        $relation = strtoupper(trim($relation));
        if(empty($cond)) {
            return $relation === "AND" ? " 1 = 1 " : " 1 = 0 ";
        }

        $condArr = [];
        foreach($cond as $key => $subCond) {
            $key = strtoupper(trim($key));
            if($key === "AND" || $key === "OR") {
                $condArr[] = '(' . static::where($bindArray, $subCond, $key, $placeHolder) . ')';
                continue;
            }
            if(count($subCond) !== 3) {
                throw new InvalidArgumentException("subCond error(count != 3): " . print_r($subCond, true));
            }
            list($column, $subRel, $value) = $subCond;
            switch(strtoupper($subRel)) {
                case "LIKE":
                    $condArr[] = static::like($bindArray, $column, $value, $placeHolder);
                    break;
                case "NOT LIKE":
                    $condArr[] = static::notLike($bindArray, $column, $value, $placeHolder);
                    break;
                case "IN":
                    $condArr[] = static::in($bindArray, $column, $value, $placeHolder);
                    break;
                case "NOT IN":
                    $condArr[] = static::notIn($bindArray, $column, $value, $placeHolder);
                    break;
                default:
                    if($placeHolder === '?') {
                        $condArr[] = "$column $subRel ?";
                        $bindArray[] = $value;
                    } else if($placeHolder === ':') {
                        $condArr[] = "$column $subRel " . static::getBindKey($bindArray, $column, $value);
                    } else {
                        throw new \InvalidArgumentException("placeHolder should be : or ?");
                    }
            }
        }
        return " " . implode(" $relation ", $condArr) . " ";
    }

    /**
     * order by
     * @param array $orderByPairs
     * @param array|null $permittedBys
     * @return string
     */
    public static function orderBy(array $orderByPairs, array $permittedBys = null) {
        $orderByArr = [];
        foreach($orderByPairs as  $by => $order) {
            $order = strtoupper(trim($order));
            if($order !== "ASC" && $order !== "DESC") {
                throw new InvalidArgumentException("BY only support ASC and DESC, but {$order} given");
            }
            if($permittedBys !== null) { // for security
                $permittedBys = array_map("strtolower", $permittedBys);
                if(!in_array(strtolower($by), $permittedBys, true)) {
                    throw new InvalidArgumentException("column `$by` is not allowed order by");
                }
            }
            $orderByArr[] = "$by $order";
        }
        return " " . implode(" , ", $orderByArr) . " ";
    }

    public static function limit($offset, $limit = null) {
        if ($limit === null) {
            return intval($offset);
        } else {
            return intval($offset) . ", " . intval($limit);
        }
    }

    public static function sanitizeColumns(array $columns, array $whiteList) {
        return array_intersect(array_map("strtolower", $columns), array_map("strtolower", $whiteList));
    }

    private static function _assertNotEmpty($var, $what) {
        if(empty($var)) {
            throw new InvalidArgumentException("$what should not be empty");
        }
    }
}