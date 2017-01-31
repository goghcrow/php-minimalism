<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/13
 * Time: 上午12:37
 */

namespace Minimalism\Scheme\Value;


class BoolType extends Value
{
    public function __toString()
    {
        return "Bool";
    }
}