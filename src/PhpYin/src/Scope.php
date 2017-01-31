<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/13
 * Time: 上午12:07
 */

namespace Minimalism\Scheme;


use Minimalism\Scheme\Value\Value;

final class Scope
{
    /**
     * @var Value[] Map<String, Value>
     * name => ["value"|"type" => Value]
     */
    public $table;

    /**
     * @var Scope
     */
    public $parent;

    public function __construct(Scope $parent = null)
    {
        $this->parent = $parent;
        $this->table = [];
    }

    public function copy()
    {
        $clone = clone $this;
        $clone->parent = null;
        return $clone;
    }

//    public function size()
//    {
//        if ($this->parent) {
//            return count($this->table) + $this->parent->size();
//        } else {
//            return count($this->table);
//        }
//    }

    /**
     * @param $name
     * @return Value|null
     */
    public function lookupValue($name)
    {
        $v = $this->lookupProperty($name, "value");
        if ($v === null) {
            return null;
        } else if ($v instanceof Value) {
            return $v;
        } else {
            Interpreter::abort("value is not a Value, shouldn't happen: $v");
            return null;
        }
    }

    /**
     * @param $name
     * @return Value|null
     */
    public function lookupLocalValue($name)
    {
        $v = $this->lookupLocalProperty($name, "value");
        if ($v === null) {
            return null;
        } else if ($v instanceof Value) {
            return $v;
        } else {
            Interpreter::abort("value is not a Value, shouldn't happen: $v");
            return null;
        }
    }

    /**
     * @param $name
     * @return Value|null
     */
    public function lookupType($name)
    {
        $v = $this->lookupProperty($name, "type");
        if ($v === null) {
            return null;
        } else if ($v instanceof Value) {
            return $v;
        } else {
            Interpreter::abort("value is not a Value, shouldn't happen: $v");
            return null;
        }
    }

    /**
     * @param $name
     * @return Value|null
     */
    public function lookupLocalType($name)
    {
        $v = $this->lookupLocalProperty($name, "type");
        if ($v === null) {
            return null;
        } else if ($v instanceof Value) {
            return $v;
        } else {
            Interpreter::abort("value is not a Value, shouldn't happen: $v");
            return null;
        }
    }

    /**
     * @param $name
     * @return mixed|null
     */
    public function lookupProperty($name, $key)
    {
        $v = $this->lookupLocalProperty($name, $key);
        if ($v !== null) {
            return $v;
        } else if ($this->parent) {
            return $this->parent->lookupProperty($name, $key);
        } else {
            return null;
        }
    }

    /**
     * @param $name
     * @return mixed|null
     */
    public function lookupLocalProperty($name, $key)
    {
        if (isset($this->table[$name])) {
            if (isset($this->table[$name][$key])) {
                return $this->table[$name][$key];
            }
        }

        return null;
    }

    /**
     * @param $name
     * @return array Map<String, Object>
     */
    public function lookupAllProps($name) {
        return isset($this->table[$name]) ? $this->table[$name] : null; // TODO or []
    }


    /**
     * @param $name
     * @return $this|Scope|null
     */
    public function findDefiningScope($name)
    {
        if (isset($this->table[$name])) {
            return $this;
        } else if ($this->parent) {
            return $this->parent->findDefiningScope($name);
        } else {
            return null;
        }
    }

    public function putProperty($name, $key, $value)
    {
        if (!isset($this->table[$name])) {
            $this->table[$name] = [];
        }
        $this->table[$name][$key] = $value;
    }

    public function putProperties($name, array $props)
    {
        if (!isset($this->table[$name])) {
            $this->table[$name] = [];
        }
        $this->table[$name] = array_merge($this->table[$name], $props);
    }

    public function putAll(Scope $other)
    {
        $this->table = array_merge($this->table, $other->table);
    }

    public function putValue($name, Value $value)
    {
        $this->putProperty($name, "value", $value);
    }

    public function putType($name, Value $value)
    {
        $this->putProperty($name, "type", $value);
    }

    public function containsKey($key)
    {
        return isset($this->table[$key]);
    }

    public function __toString()
    {
        $str = "";
        foreach ($this->table as $name => $props) {
            $str .= Constants::VECTOR_BEGIN . "$name ";
            foreach ($props as $key => $prop) {
                $str .= ":$key $prop";
            }
            $str .= Constants::VECTOR_END;
        }
        return $str;
    }
}