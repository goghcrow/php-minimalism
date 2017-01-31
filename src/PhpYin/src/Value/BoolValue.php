<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/13
 * Time: 上午12:37
 */

namespace Minimalism\Scheme\Value;


class BoolValue extends Value
{
    public $value;

    public function __construct($value)
    {
        $this->value = boolval($value);
    }

    public function __toString()
    {
        return $this->value ? "true" : "false";
    }
}