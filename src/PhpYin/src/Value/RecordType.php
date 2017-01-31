<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/15
 * Time: 下午4:03
 */

namespace Minimalism\Scheme\Value;


use Minimalism\Scheme\Ast\Node;
use Minimalism\Scheme\Constants;
use Minimalism\Scheme\Scope;

class RecordType extends Value
{
    /* @var string */
    public $name;
    /* @var Node */
    public $definition;
    /* @var Scope */
    public $properties;

    // TODO remove definition
    public function __construct($name, Node $definition, Scope $properties)
    {
        $this->name = $name;
        $this->definition = $definition;
        $this->properties = $properties->copy();
    }

    public function __toString()
    {
        $body = "";
        foreach ($this->properties->table as $field => $props) {
            $arrBody = "";

            foreach ($props as $key => $value) {
                if ($value !== null) {
                    $arrBody .= " :$key $value";
                }
            }
            $body .= " " . Constants::VECTOR_BEGIN . $field . $arrBody . Constants::VECTOR_END;
        }

        // _ 匿名 record
        $name = $this->name === null ? "_" : $this->name;
        return Constants::TUPLE_BEGIN . Constants::RECORD_KEYWORD . " {$name}{$body}" . Constants::TUPLE_END;
    }
}