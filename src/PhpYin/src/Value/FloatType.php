<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/13
 * Time: 上午12:39
 */

namespace Minimalism\Scheme\Value;


class FloatType extends Value
{
    public function __toString()
    {
        return "Float";
    }
}