<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/13
 * Time: 上午12:39
 */

namespace Minimalism\Scheme\Value;


class FloatValue extends Value
{
    public $value;

    public function __construct($value)
    {
        $this->value = floatval($value);
    }

    public function __toString()
    {
        return strval($this->value);
    }
}