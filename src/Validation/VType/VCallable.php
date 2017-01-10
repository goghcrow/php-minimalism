<?php

namespace Minimalism\Validation\VType;


use Closure;
use InvalidArgumentException;

class VCallable extends VType
{
    public function __invoke(/*...$args*/) {
        return call_user_func_array($this->var, func_get_args());
    }

    /**
     * @param \Exception $ex
     * @return Closure
     * @throws \Exception
     */
    public function orThrow(\Exception $ex = null) {
        if ($this instanceof VNil) {
            if ($ex === null) {
                $ex = new InvalidArgumentException($this->var ?: "Invalid Callable");
            }
            throw $ex;
        }
        return function(/*...$args*/) {
            return call_user_func_array($this->var, func_get_args());
        };
    }

    /**
     * @param $else
     * @return Closure
     */
    public function orElse($else) {
        if ($this instanceof VNil) {
            return $else;
        }
        return function(/*...$args*/) {
            return call_user_func_array($this->var, func_get_args());
        };
    }
}