<?php

namespace Minimalism\Validation\VType;


use Minimalism\Validation\V;

class VObject extends VType
{
    /**
     * @param $k
     * @return VMixed
     */
    public function prop($k) {
        if (!property_exists($this->var, $k)) {
            return V::ofNil("Property $k Not Exist In Object " . get_class($this->var));
        }
        return VMixed::of($this->var->$k);
    }
}