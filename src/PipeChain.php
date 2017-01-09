<?php

namespace Minimalism;

/**
 * Class PipeChain
 *
 * idea from elixir
 *
 * TODO 支持pipechain的idea插件
 */
class PipeChain
{
    private $value;

    private function __construct() { }

    /**
     * @param $value
     * @return static
     */
    public static function of($value)
    {
        $self = new static;
        $self->value = $value;
        return $self;
    }

    public function get()
    {
        return $this->value;
    }

    public function __call($name, $arguments)
    {
        $this->value = $name($this->value, ...$arguments);
        return $this;
    }
}