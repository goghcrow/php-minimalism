<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/1/28
 * Time: 下午4:40
 */

namespace Minimalism\Scheme\Extension;


use Minimalism\Scheme\Ast\Node;
use Minimalism\Scheme\Interpreter;
use Minimalism\Scheme\Parser\Parser;
use Minimalism\Scheme\Scope;
use Minimalism\Scheme\Value\BuildInFun;
use Minimalism\Scheme\Value\StringValue;
use Minimalism\Scheme\Value\Value;

class Require_ extends BuildInFun
{
    public function __construct()
    {
        parent::__construct("require", 1);
    }

    /**
     * @param Value[] $args Values
     * @param Node $location
     * @param Scope $s
     * @return Value
     */
    public function apply(array $args, Node $location, Scope $s)
    {
        $v1 = $args[0];
        if ($v1 instanceof StringValue) {
            $dir = dirname($location->file);
            // 暂时支持相对路径
            $file = "$dir/$v1->value";
            if (!file_exists($file)) {
                Interpreter::abort("required file not exists: $dir", $location);
            }
            $program = Parser::parse($file);
            return $program->interp($s);
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