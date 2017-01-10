<?php

namespace Minimalism\Validation\Predicate;


use Closure;

class Predicate
{
    /**
     * @var $predicate callable
     */
    protected $predicate = null;

    private function __construct($predicate) {
        $this->predicate = $predicate;
    }

    /**
     * @param callable $predicate
     * @return static
     */
    public static function of($predicate = null) {
        return new static($predicate);
    }

    public function __invoke(/*...$args*/) {
        return call_user_func_array($this->predicate, func_get_args());
    }

    /**
     * @param callable $predicate
     * @return Closure
     */
    protected function pipe($predicate) {
        $prev = $this->predicate;
        return function($arg) use($prev, $predicate){
            if ($prev === null) {
                return call_user_func($predicate, $arg);
            }

            $arg = call_user_func($prev, $arg);
            if ($arg instanceof PNil) {
                return $arg;
            }

            return call_user_func($predicate, $arg);
        };
    }
}