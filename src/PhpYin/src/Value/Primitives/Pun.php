<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/1/27
 * Time: 下午1:25
 */

namespace Minimalism\Scheme\Value\Primitives;


use Minimalism\Scheme\Ast\Node;
use Minimalism\Scheme\Scope;
use Minimalism\Scheme\Value\PrimFun;
use Minimalism\Scheme\Value\Value;

class Pun extends PrimFun
{
    public function __construct($name)
    {
        // 不定参数
        parent::__construct($name, -1);
    }

    /**
     * @param Value[] $args Values
     * @param Node $location
     * @return Value
     */
    public function apply(array $args, Node $location)
    {
        $pun = $this->name;

        $realArgs = [];
        foreach ($args as $argValue) {
            if (isset($argValue->value)) {
                $realArgs[] = $argValue->value;
            } else {
                // TODO
            }
        }
        $phpRet = $pun(...$realArgs);
        return Value::from($phpRet);
    }

    /**
     * @param Value[] $args Types
     * @param Node $location
     * @return Value
     */
    public function typecheck(array $args, Node $location)
    {
        // TODO: Implement typecheck() method.
    }
}