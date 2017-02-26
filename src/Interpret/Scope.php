<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/5
 * Time: 下午10:18
 */

namespace Minimalism\A\Interpret;


final class Scope implements \ArrayAccess
{
    public $table;

    public $parent;

    public function __construct(Scope $parent = null)
    {
        $this->parent = $parent;
        $this->table = [];
    }

    public function lookup($name)
    {
        $v = $this->lookupLocal($name);
        if ($v !== null) {
            return $v;
        } else if ($this->parent) {
            return $this->parent->lookup($name);
        } else {
            return null;
        }
    }

    public function lookupLocal($name)
    {
        if (isset($this->table[$name])) {
            return $this->table[$name];
        }
        return null;
    }

    public function putValue($name, $value)
    {
        $this->table[$name] = $value;
    }

    public function putAll(Scope $other)
    {
        $this->table = array_merge($this->table, $other->table);
    }

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

    public function offsetExists($name)
    {
        return $this->lookup($name) !== null;
    }

    public function offsetGet($name)
    {
        return $this->lookup($name);
    }

    public function offsetSet($name, $value)
    {
        $this->table[$name] = $value;
    }

    public function offsetUnset($name)
    {
        unset($this->table[$name]);
    }
}