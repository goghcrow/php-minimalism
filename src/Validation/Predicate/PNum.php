<?php

namespace Minimalism\Validation\Predicate;


use Minimalism\Validation\P;
use Minimalism\Validation\VType\VNil;

class PNum extends Predicate
{

    public function eq($val) {
        $this->predicate = $this->pipe(function($var) use($val) {
            if ($var instanceof PNil) {
                return $var;
            }
            if ($var === $val) {
                return $var;
            }
            return P::ofNil();
        });
        return $this;
    }

    /**
     * greater than
     * @param $min
     * @return $this|VNil
     */
    public function gt($min) {
        $this->predicate = $this->pipe(function($var) use($min) {
            if ($var instanceof PNil) {
                return $var;
            }
            if ($var > $min) {
                return $var;
            }
            return P::ofNil();
        });
        return $this;
    }

    /**
     * greater than or equal
     * @param $min
     * @return $this|VNil
     */
    public function ge($min) {
        $this->predicate = $this->pipe(function($var) use($min) {
            if ($var instanceof PNil) {
                return $var;
            }
            if ($var >= $min) {
                return $var;
            }
            return P::ofNil();
        });
        return $this;
    }

    /**
     * less than
     * @param $max
     * @return $this|VNil
     */
    public function lt($max) {
        $this->predicate = $this->pipe(function($var) use($max) {
            if ($var instanceof PNil) {
                return $var;
            }
            if ($var < $max) {
                return $var;
            }
            return P::ofNil();
        });
        return $this;
    }

    /**
     * less than or equal
     * @param $max
     * @return $this|VNil
     */
    public function le($max) {
        $this->predicate = $this->pipe(function($var) use($max) {
            if ($var instanceof PNil) {
                return $var;
            }
            if ($var <= $max) {
                return $var;
            }
            return P::ofNil();
        });
        return $this;
    }

    public function between($min, $max) {
        $this->predicate = $this->pipe(function($var) use($min, $max) {
            if ($var instanceof PNil) {
                return $var;
            }
            if ($var >= $min && $var <= $max) {
                return $var;
            }
            return P::ofNil();
        });
        return $this;
    }
}