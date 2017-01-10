<?php

namespace Minimalism\Validation\VType;


use Minimalism\Validation\V;

class VIp extends VString
{

    public function orElse($else = "0.0.0.0") {
        return parent::orElse($else);
    }

    /**
     * @return VInt
     */
    public function toLong() {
        $var = ip2long($this->var);
        if ($var === false) {
            return V::ofNil("Invalid IP (ip2long fail)");
        }
        return VInt::of($var);
    }
}