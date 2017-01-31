<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/13
 * Time: 上午12:44
 */

namespace Minimalism\Scheme\Value;


class StringValue extends Value
{
    public $value;

    public function __construct($value)
    {
        $this->value = strval($value);
    }

    public function __toString()
    {
        return $this->value;
    }
}