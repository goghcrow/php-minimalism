<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/13
 * Time: 上午1:03
 */

namespace Minimalism\Scheme\Value;


use Minimalism\Scheme\Constants;

class UnionType extends Value
{
    /**
     * Types HashSet
     * @var Value[]
     */
    public $values;

    public function __construct()
    {
        $this->values = [];
    }

    public function add(Value $value)
    {
        if ($value instanceof UnionType) {
            $this->values = array_merge($this->values, $value->values);
        } else {
            // TODO replace spl_object_storage
            $this->values[get_class($value)] = $value;
            // $this->values[spl_object_hash($value)] = $value;
        }
    }

    /**
     * @param Value[] ...$values
     * @return UnionType|Value
     */
    public static function union(Value ...$values)
    {
        $u = new UnionType();
        foreach ($values as $value) {
            $u->add($value);
        }

        if (count($u->values) === 1) {
            return $u->values[0];
        } else {
            return $u;
        }
    }

    public function contains(Value $value)
    {
        // TODO replace spl_object_storage
        return isset($this->values[get_class($value)]);
        // return isset($this->values[spl_object_hash($value)]);
    }

    public function __toString()
    {
        return Constants::TUPLE_BEGIN . "U " . implode(" ", $this->values) . Constants::TUPLE_END;
    }
}