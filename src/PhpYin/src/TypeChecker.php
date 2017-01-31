<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/15
 * Time: 下午4:05
 */

namespace Minimalism\Scheme;


use Minimalism\Scheme\Ast\Declare_;
use Minimalism\Scheme\Ast\Node;
use Minimalism\Scheme\Parser\Parser;
use Minimalism\Scheme\Value\FunType;
use Minimalism\Scheme\Value\Type;
use Minimalism\Scheme\Value\Value;
use SplObjectStorage;

final class TypeChecker
{
    /* @var TypeChecker */
    public static $self;
    public $file;

    /**
     * Set<FunType>
     * @var SplObjectStorage
     */
    public $uncalled;

    /**
     * Set<FunType>
     * @var SplObjectStorage
     */
    public $callStack;

    public function __construct($file)
    {
        $this->file = $file;
        $this->uncalled = new SplObjectStorage;
        $this->callStack = new SplObjectStorage;
    }

    /**
     * @param string $file
     * @return Value
     */
    public function typecheck($file)
    {
        $program = Parser::parse($file);
        $s = Interpreter::buildInitTypeScope();
        $ret = $program->typecheck($s);

        // TODO
        while ($this->uncalled->count() > 0) {
            $toRemove = new \SplObjectStorage();
            $toRemove->addAll($this->uncalled);
            /** @var FunType $ft */
            foreach ($toRemove as $ft) {
                $this->invokeUncalled($ft, $s);
            }
            $this->uncalled->removeAll($toRemove);
        }

        return $ret;
    }

    public function invokeUncalled(FunType $fun, Scope $s)
    {
        $funScope = new Scope($fun->env);
        if ($fun->properties !== null) {
            Declare_::mergeType($fun->properties, $funScope);
        }

        self::$self->callStack->attach($fun);
        $actual = $fun->fun->body->typecheck($funScope);
        self::$self->callStack->detach($fun);

        $retNode = $fun->properties->lookupLocalProperty(Constants::RETURN_ARROW, "type");

        if ($retNode === null || !($retNode instanceof Node)) {
            Interpreter::abort("illegal return type: retNode");
            return;
        }

        $expected = $retNode->typecheck($funScope);
        if (!Type::subtype($actual, $expected, true)) {
            Interpreter::abort("type error in return value, expected: $expected, actual: $actual", $fun->fun);
        }
    }
}