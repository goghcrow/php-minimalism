<?php

namespace Minimalism\Validation\VType;


use Minimalism\Validation\V;

class VMixed extends VType
{
    public function orElse($else = null) {
        return parent::orElse($else);
    }


    /**
     * @return $this|VNil
     * 冗余方法, 可以通过 ->vAssert(P::isEmpty()) 做断言
     */
    public function beEmpty() {
        if (empty($this->var)) {
            return $this;
        }
        return V::ofNil("Assert Fail: Is Empty");
    }

    /**
     * @return $this|VNil
     * 冗余方法, 可以通过 ->vAssert(P::notEmpty()) 做断言
     */
    public function notEmpty() {
        if (!empty($this->var)) {
            return $this;
        }
        return V::ofNil("Assert Fail: Not Empty");
    }

    /**
     * @param null $opts
     * @return VInt
     */
    public function toInt($opts = null) {
        return V::ofInt($this->var, $opts);
    }

    /**
     * @param null $opts
     * @return VFloat
     */
    public function toFloat($opts = null) {
        return V::ofFloat($this->var, $opts);
    }

    /**
     * @return VArray
     */
    public function toArray() {
        return V::ofArray($this->var);
    }

    /**
     * @return VObject
     */
    public function toObject() {
        return V::ofObject($this->var);
    }

    /**
     * @return VBool
     */
    public function toBool() {
        return V::ofBool($this->var);
    }
}
