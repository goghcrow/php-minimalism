<?php

namespace Minimalism\Validation\VType;

use Minimalism\Validation\Predicate\PNil;
use Minimalism\Validation\Predicate\Predicate;
use Minimalism\Validation\V;
use InvalidArgumentException;

class VType
{
    protected $var;

    private function __construct($var) {
        if ($var instanceof VNil) {
            throw new \InvalidArgumentException('Invalid: $var instanceof VNil');
        }
        $this->var = $var;
    }

    /**
     * @param $var
     * @return static
     */
    public static function of($var) {
        return new static($var);
    }

    /**
     * @return bool
     */
    public function valid() {
        return !($this instanceof VNil);
    }

    /**
     * @return mixed|VNil
     */
    public function get() {
        if ($this instanceof VNil) {
            return $this;
        }
        return $this->var;
    }

    /**
     * @param \Exception $ex
     * @return mixed
     * @throws \Exception
     */
    public function orThrow(\Exception $ex = null) {
        if ($this instanceof VNil) {
            if ($ex === null) {
                // 配置异常类型&msg&code
                $ex = new InvalidArgumentException($this->var ?: "Invalid Argument.");
            }
            throw $ex;
        }
        return $this->var;
    }

    /**
     * @param $else
     * @return mixed
     */
    public function orElse($else) {
        if ($this instanceof VNil) {
            return $else;
        }
        return $this->var;
    }

    /*
    public function vMap($callable, ...$exArgs) {
        $this->var = call_user_func($callable, $this->var, ...$exArgs);
        return VMixed::of($this->var);
    }
    */

    /**
     * @return VMixed
     * 兼容php5.6预发
     * 
     * 可以用作对该类型函数进行兼容
     * V::ofString()->vMap("trim", "\t")
     * V::ofArray([5, 4, 3, 2, 1])->vMap(function($arr) { sort($arr); return $arr;})->get()
     */
    public function vMap(/*$callable, ...$exArgs*/) {
        $args = func_get_args();
        array_splice($args, 1, 0, [$this->var]); // 将 $this->var, 插入数组index-1位置
        $this->var = call_user_func_array("call_user_func", $args);
        return VMixed::of($this->var);
    }
    
    /**
     * @param Predicate $predicate
     * @return $this|VNil
     */
    public function vAssert(Predicate $predicate) {
        if ($this instanceof VNil) {
            return $this; // VNil
        }
        // 断言失败返回VNil
        if ($predicate($this->var) instanceof PNil) {
            return V::ofNil("vAssert Fail");
        }
        return $this;
    }
}