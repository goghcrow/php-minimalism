<?php

namespace Minimalism\Validation\VType;


class VBool extends VType
{
    public function orElse($else = false) {
        return parent::orElse($else);
    }
}
