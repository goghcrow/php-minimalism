<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/13
 * Time: 上午12:18
 */

namespace Minimalism\Scheme\Value;


class VoidValue extends Value
{
    public function __toString()
    {
        return "void";
    }
}