<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/1/27
 * Time: 下午1:04
 */

namespace Minimalism\Scheme\Value;


class ResourceValue extends Value
{
    public $value;
    public $id;
    public $type;

    public function __construct($value)
    {
        assert(is_resource($value));
        $this->value = $value;
        $this->id = intval($value);
        $this->type = get_resource_type($this->value);
    }

    public function __toString()
    {
        return "(Resource :id $this->id :type $this->type)";
    }
}