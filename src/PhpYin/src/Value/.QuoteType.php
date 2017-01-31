<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/1/29
 * Time: 下午1:03
 */

namespace Minimalism\Scheme\Value;


class QuoteType extends Value
{

    public function __toString()
    {
        return "Quote";
    }
}