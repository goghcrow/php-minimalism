<?php

namespace Minimalism\Validation\VType;


class VFloat extends VNum
{
    public function orElse($else = 0.0) {
        return parent::orElse($else);
    }
}