<?php

namespace Minimalism\Validation\Predicate;


use Minimalism\Validation\P;

class PArray extends Predicate
{
    public function count() {
        $this->predicate = $this->pipe(function($var) {
            if ($var instanceof PNil) {
                return $var;
            }
            return count($var);
        });
        return P::ofInt($this->predicate);
    }

    public function all($predicate) {
        $this->predicate = $this->pipe(function($var) use($predicate) {
            if ($var instanceof PNil) {
                return $var;
            }
            if (count($var) === 0) {
                return PNil::of();
            }

            foreach ($var as $key => $value) {
                if (!call_user_func($predicate, $value)) {
                    return PNil::of();
                }
            }
            return $var;
        });
        return $this;
    }

    public function any($predicate) {
        $this->predicate = $this->pipe(function($var) use($predicate) {
            if ($var instanceof PNil) {
                return $var;
            }
            if (count($var) === 0) {
                return PNil::of();
            }

            foreach ($var as $key => $value) {
                if (call_user_func($predicate, $value)) {
                    return $var;
                }
            }
            return PNil::of();
        });
        return $this;
    }

}