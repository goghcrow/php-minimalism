<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/15
 * Time: 下午4:03
 */

namespace Minimalism\Scheme\Value;


use Minimalism\Scheme\Constants;
use Minimalism\Scheme\Scope;

class RecordValue extends Value
{
    /* @var string */
    public $name;
    /* @var RecordType */
    public $type;
    /* @var Scope */
    public $properties;

    /**
     * RecordValue constructor.
     * @param string|null $name
     * @param RecordType $type
     * @param Scope $properties
     */
    public function __construct($name, RecordType $type, Scope $properties)
    {
        $this->name = $name;
        $this->type = $type;
        $this->properties = $properties;
    }

    public function __toString()
    {
        $propStr = "";
        foreach ($this->properties->table as $field => $_) {
            $propStr .= " " . Constants::VECTOR_BEGIN . "$field " . $this->properties->lookupLocalValue($field) . Constants::VECTOR_END;
        }

        $name = $this->name === null ? "_" : $this->name;
        return Constants::TUPLE_BEGIN . Constants::RECORD_KEYWORD . " {$name}{$propStr}" . Constants::TUPLE_END;
    }
}