<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/13
 * Time: 上午12:43
 */

namespace Minimalism\Scheme\Value;


class StringType extends Value
{
    public function __toString()
    {
        return "String";
    }
}