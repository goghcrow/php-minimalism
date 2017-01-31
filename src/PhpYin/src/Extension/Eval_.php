<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/1/29
 * Time: 上午9:56
 */

namespace Minimalism\Scheme\Extension;


use Minimalism\Scheme\Ast\Node;
use Minimalism\Scheme\Ast\Tuple;
use Minimalism\Scheme\Interpreter;
use Minimalism\Scheme\Parser\Parser;
use Minimalism\Scheme\Scope;
use Minimalism\Scheme\Value\BuildInFun;
use Minimalism\Scheme\Value\StringValue;
use Minimalism\Scheme\Value\Value;

class Eval_ extends BuildInFun
{
    public function __construct()
    {
        parent::__construct("eval", 1);
    }

    /**
     * @param Value[] $args Values
     * @param Node $location
     * @param Scope $s
     * @return Value
     *
     * 20170128 加入scope参数是为了实现require， 不知道是否有更好的方式
     */
    public function apply(array $args, Node $location, Scope $s)
    {
        $v1 = $args[0];
        // TODO 加入scope 参数
        // $scope = $args[1];
        if ($v1 instanceof Tuple) {
            $eval = Parser::parserStr($v1->value, $location);
            return $eval->interp($s);
        }

        Interpreter::abort("incorrect argument types for require: $v1", $location);
        return null;
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