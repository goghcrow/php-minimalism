<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/13
 * Time: 上午12:20
 */

namespace Minimalism\Scheme\Value;


class AnyType extends Value
{
    public function __toString()
    {
        return "Any";
    }
}