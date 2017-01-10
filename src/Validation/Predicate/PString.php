<?php

namespace Minimalism\Validation\Predicate;


use Minimalism\Validation\P;

class PString extends Predicate
{

    /**
     * @return PInt
     */
    public function len() {
        $this->predicate = $this->pipe(function($var) {
            if ($var instanceof PNil) {
                return $var;
            }
            $v = mb_strlen($var, "UTF-8");
            if ($v === false) {
                return P::ofNil();
            }
            return $v;
        });

        return P::ofInt($this->predicate);
    }

    public function startWith($needle) {
        $this->predicate = $this->pipe(function($var) use($needle) {
            if ($var instanceof PNil) {
                return $var;
            }

            if ($needle === "" || substr($var, 0, strlen($needle)) === $needle) {
                return $var;
            }

            return P::ofNil();
        });

        return $this;
    }

    public function endWith($needle) {
        $this->predicate = $this->pipe(function($var) use($needle) {
            if ($var instanceof PNil) {
                return $var;
            }

            if ($needle === "" || substr($var, -strlen($needle)) === $needle) {
                return $var;
            }

            return P::ofNil();
        });

        return $this;
    }
}