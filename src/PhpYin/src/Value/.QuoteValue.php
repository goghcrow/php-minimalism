<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/1/29
 * Time: 下午1:03
 */

namespace Minimalism\Scheme\Value;


use Minimalism\Scheme\Ast\Block;

class QuoteValue extends Value
{
    public $value;

    public function __construct(Block $value)
    {
        $this->value = $value;
    }

    public function __toString()
    {
        return $this->value->__toString();
    }
}