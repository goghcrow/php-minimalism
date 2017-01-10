<?php

namespace Minimalism\Validation\Predicate;


class PNil extends Predicate
{
    public function __call($name, $arguments) {
        return $this;
    }
}
