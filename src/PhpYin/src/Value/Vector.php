<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/15
 * Time: 下午4:02
 */

namespace Minimalism\Scheme\Value;


use Minimalism\Scheme\Constants;

class Vector extends Value
{
    /* @var Value[] */
    public $values;

    public function __construct(array $values)
    {
        $this->values = $values;
    }

    public function set($idx, Value $value) {
        $this->values[$idx] = $value;
    }

    public function size()
    {
        return count($this->values);
    }

    public function __toString()
    {
        return Constants::VECTOR_BEGIN . implode(" ", $this->values) . Constants::VECTOR_END;
    }
}