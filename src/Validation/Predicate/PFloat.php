<?php

namespace Minimalism\Validation\Predicate;


use Minimalism\Validation\P;

class PFloat extends PNum
{
    public function eq($val, $precise = 0.00000001) {
        $this->predicate = $this->pipe(function($var) use($val, $precise) {
            if ($var instanceof PNil) {
                return $var;
            }
            if (abs($var - $val) <= $precise) {
                return $var;
            }
            return P::ofNil();
        });
        return $this;
    }
}