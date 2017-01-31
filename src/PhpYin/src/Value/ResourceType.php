<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/1/27
 * Time: 下午1:04
 */

namespace Minimalism\Scheme\Value;


class ResourceType extends Value
{
    public function __construct()
    {
    }

    public function __toString()
    {
        return "Resource";
    }
}