<?php

namespace Minimalism\Validation\VType;


use Minimalism\Validation\V;

class VNum extends VType {
    public function orElse($else = 0) {
        return parent::orElse($else);
    }

    public function range($min, $max) {
        $this->var = min($max, max($min, $this->var));
        return $this;
    }

    public function min($min) {
        $this->var = max($min, $this->var);
        return $this;
    }

    public function max($max) {
        $this->var = min($max, $this->var);
        return $this;
    }

    public function ceil() {
        // ceil return float
        $this->var = ceil($this->var);
        return V::ofInt($this->var);
    }

    public function floor() {
        // floor return float
        $this->var = floor($this->var);
        return V::ofInt($this->var);
    }





    /**
     * @param $var
     * @return $this|VNil
     */
    public function eq($var) {
        if ($this->var === $var) {
            return $this;
        }
        return V::ofNil("Not EQ $var");
    }

    /**
     * greater than
     * @param $min
     * @return $this|VNil
     */
    public function gt($min) {
        if ($this->var > $min) {
            return $this;
        }
        return V::ofNil("Not GT $min");
    }

    /**
     * greater than or equal
     * @param $min
     * @return $this|VNil
     */
    public function ge($min) {
        if ($this->var >= $min) {
            return $this;
        }
        return V::ofNil("NOT GE $min");
    }

    /**
     * less than
     * @param $max
     * @return $this|VNil
     */
    public function lt($max) {
        // return $this->cond(C::ofInt()->lt($max));
        if ($this->var < $max) {
            return $this;
        }
        return V::ofNil("NOT LT $max");
    }

    /**
     * less than or equal
     * @param $max
     * @return $this|VNil
     */
    public function le($max) {
        if ($this->var <= $max) {
            return $this;
        }
        return V::ofNil("NOT LE");
    }

    public function between($min, $max) {
        if ($this->var >= $min && $this->var <= $max) {
            return $this;
        }
        return V::ofNil("NOT BETWEEN $min and $max");
    }
}