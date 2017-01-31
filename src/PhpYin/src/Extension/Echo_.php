<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/1/20
 * Time: 上午1:06
 */

namespace Minimalism\Scheme\Extension;


use Minimalism\Scheme\Ast\Node;
use Minimalism\Scheme\Scope;
use Minimalism\Scheme\Value\BuildInFun;
use Minimalism\Scheme\Value\Value;

class Echo_ extends BuildInFun
{
    public function __construct()
    {
        // 还是让echo 只接受一个参数而不是 -1 不定参数
        // 因为可以使用 echo [vec...]
        parent::__construct("echo", 1);
    }

    /**
     * @param Value[] $args Values
     * @param Node $location
     * @param Scope $s
     * @return Value
     */
    public function apply(array $args, Node $location, Scope $s)
    {
        echo $args[0];
        return Value::$VOID;
    }

    /**
     * @param Value[] $args Types
     * @param Node $location
     * @return Value
     */
    public function typecheck(array $args, Node $location)
    {
        return null;
    }
}