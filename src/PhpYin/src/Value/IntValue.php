<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/13
 * Time: 上午12:42
 */

namespace Minimalism\Scheme\Value;


class IntValue extends Value
{
    public $value;

    public function __construct($value)
    {
        $this->value = intval($value);
    }

    public function __toString()
    {
        return strval($this->value);
    }
}