<?php

namespace Minimalism\Validation\VType;


class VNil extends VType
{
    public function __call($name, $arguments) {
        return $this;
    }

    // TODO
//    public function orElse($else = null) {
//        return parent::orElse($else);
//    }
}