<?php

namespace Minimalism\Validation\Predicate;


class PMixed extends Predicate
{
    public function isEmpty() {
        $this->predicate = $this->pipe(function($var) {
            if ($var instanceof PNil) {
                return $var;
            }

            if (empty($var)) {
                return $var;
            }
            return PNil::of();
        });

        return $this;
    }

    public function notEmpty() {
        $this->predicate = $this->pipe(function($var) {
            if ($var instanceof PNil) {
                return $var;
            }

            if (empty($var)) {
                return PNil::of();
            }
            return $var;
        });

        return $this;
    }
}
