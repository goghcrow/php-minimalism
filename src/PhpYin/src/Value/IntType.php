<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/13
 * Time: 上午12:41
 */

namespace Minimalism\Scheme\Value;


class IntType extends Value
{
    public function __toString()
    {
        return "Int";
    }
}