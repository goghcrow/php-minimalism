<?php

namespace Minimalism\Validation\VType;


use Minimalism\Stream\Stream;
use Minimalism\Validation\V;

/**
 * Class VArray
 * @package Minimalism\Validation\VType
 *
 * TODO 加入Stream
 */
class VArray extends VType
{

    public function orElse($else = []) {
        return parent::orElse($else);
    }

    /**
     * @return Stream
     *
     * 是否去掉该方法:
     * 1. 对Stream依赖
     * 2. 不统一, 转成Stream之后, 则失去VType类的方法,比如orElse等
     * 只能通过 $ret instanceof VNil 对返回值判断
     */
    public function toStream() {
        return Stream::of($this->var);
    }

    /**
     * @param $k
     * @return VMixed
     */
    public function key($k) {
        if (count($this->var) === 0 || !isset($this->var[$k])) {
            return V::ofNil("Undefined index: $k");
        }
        return VMixed::of($this->var[$k]);
    }

    /**
     * @param string $path k1.k2.k3
     * @return VMixed
     */
    public function visit($path) {
        $tmp = $this->var;
        $pathArr = explode(".", $path);
        foreach ($pathArr as $key) {
            if (!is_array($tmp) || !isset($tmp[$key])) {
                return V::ofNil("Undefined index path: $path");
            }
            $tmp = $tmp[$key];
        }
        return VMixed::of($tmp);
    }

    /**
     * @return VMixed|VNil
     */
    public function first() {
        if (count($this->var) === 0) {
            return V::ofNil("Empty Array");
        }
        return VMixed::of(reset($this->var));
    }

    public function last() {
        if (count($this->var) === 0) {
            return V::ofNil("Empty Array");
        }
        return VMixed::of(end($this->var));
    }

    /**
     * @return VInt
     */
    public function count() {
        return VInt::of(count($this->var));
    }

    /**
     * @param $predicate
     * @return VBool
     */
    public function all($predicate) {
        if (count($this->var) === 0) {
            return V::ofBool(false);
        }

        foreach ($this->var as $key => $value) {
            if (!call_user_func($predicate, $value)) {
                return V::ofBool(false);
            }
        }
        return V::ofBool(true);
    }

    /**
     * @param $predicate
     * @return VBool
     */
    public function any($predicate) {
        if (count($this->var) === 0) {
            return V::ofBool(false);
        }

        foreach ($this->var as $key => $value) {
            if (call_user_func($predicate, $value)) {
                return V::ofBool(true);
            }
        }
        return V::ofBool(false);
    }

    /**
     * @param $needle
     * @param bool $strict
     * @return VBool
     */
    public function contains($needle, $strict = false) {
        return V::ofBool(array_search($needle, $this->var, $strict) !== false);
    }

    /**
     * @param null $sort_flags
     * @return $this
     */
    public function unique($sort_flags = null) {
        $this->var = array_unique($this->var, $sort_flags);
        return $this;
    }

    public function column($key) {
        $this->var = array_column($this->var, $key);
        return $this;
    }

    public function reindex($key) {
        $this->var = array_column($this->var, null, $key);
        return $this;
    }

    /**
     * @param $sep
     * @return VString
     */
    public function join($sep) {
        return VString::of(implode($sep, $this->var));
    }

    /**
     * @param $callable
     * @return $this
     */
    public function map($callable) {
        $this->var = array_map($callable, $this->var);
        return $this;
    }

    public function filter($callable) {
        $this->var = array_filter($this->var, $callable, ARRAY_FILTER_USE_BOTH);
        return $this;
    }

    public function reduce($callback, $initial = null) {
        return array_reduce($this->var, $callback, $initial);
    }

    // public function keys($search = null, $strict = false) {
    // array_keys($this->var, $search, $strict);
    // }

    // public function values() {}

    // public function sort() {}
}