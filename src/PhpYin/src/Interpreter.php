<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/15
 * Time: 下午8:50
 */

namespace Minimalism\Scheme;


use Minimalism\Scheme\Ast\Node;
use Minimalism\Scheme\Extension\Echo_;
use Minimalism\Scheme\Extension\Eval_;
use Minimalism\Scheme\Extension\Require_;
use Minimalism\Scheme\Parser\Parser;
use Minimalism\Scheme\Value\AnyType;
use Minimalism\Scheme\Value\BoolType;
use Minimalism\Scheme\Value\BoolValue;
use Minimalism\Scheme\Value\FloatType;
use Minimalism\Scheme\Value\IntType;
use Minimalism\Scheme\Value\Primitives\Add;
use Minimalism\Scheme\Value\Primitives\And_;
use Minimalism\Scheme\Value\Primitives\Div;
use Minimalism\Scheme\Value\Primitives\Eq;
use Minimalism\Scheme\Value\Primitives\Gt;
use Minimalism\Scheme\Value\Primitives\GtE;
use Minimalism\Scheme\Value\Primitives\Lt;
use Minimalism\Scheme\Value\Primitives\LtE;
use Minimalism\Scheme\Value\Primitives\Mult;
use Minimalism\Scheme\Value\Primitives\Not;
use Minimalism\Scheme\Value\Primitives\Or_;
use Minimalism\Scheme\Value\Primitives\Pun;
use Minimalism\Scheme\Value\Primitives\Sub;
use Minimalism\Scheme\Value\StringType;
use Minimalism\Scheme\Value\Type;
use Minimalism\Scheme\Value\UnionType;
use Minimalism\Scheme\Value\Value;
use Minimalism\Scheme\Value\VoidValue;


final class Interpreter
{
    public $scope;

    public function __construct()
    {
        self::init();
        $this->scope = self::buildInitScope();
        self::buildPhpScope($this->scope);
    }

    /**
     * @param $file
     * @return Value
     */
    public function interp($file)
    {
        $program = Parser::parse($file);
        return $program->interp($this->scope);
    }

    public static function abort($msg, Node $loc = null)
    {
        $msg = $loc === null ? $msg : $loc->getFileLineCol() . " $msg";
        fprintf(STDERR, "$msg\n");
        debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        exit(1);
    }

    public static function init()
    {
        Value::$VOID = new VoidValue;

        Value::$ANY = new AnyType;

        Type::$BOOL = new BoolType;
        Type::$INT = new IntType;
        Type::$FLOAT = new FloatType;
        Type::$STRING = new StringType;
    }

    /**
     * @return Scope
     * TODO 加入php原生函数到init Scope
     */
    public static function buildInitScope()
    {
        $init = new Scope();

        // TODO 这里的key是否使用PrimFun的name属性
        $init->putValue("+", new Add());
        $init->putValue("-", new Sub());
        $init->putValue("*", new Mult());
        $init->putValue("/", new Div());

        $init->putValue("<", new Lt());
        $init->putValue("<=", new LtE());
        $init->putValue(">", new Gt());
        $init->putValue(">=", new GtE());
        $init->putValue("=", new Eq());
        $init->putValue("and", new And_());
        $init->putValue("or", new Or_());
        $init->putValue("not", new Not());

        $init->putValue("true", new BoolValue(true));
        $init->putValue("false", new BoolValue(false));

        $init->putValue("Int", Type::$INT);
        $init->putValue("Bool", Type::$BOOL);
        $init->putValue("String", Type::$STRING);


        // 扩充, ?! 是否应该加入Void
        $init->putValue("echo", new Echo_());
        $init->putValue("require", new Require_());
        $init->putValue("eval", new Eval_());
        $init->putValue("void", Value::$VOID);

        return $init;
    }

    private static function buildPhpScope(Scope $s)
    {
        $constants = get_defined_constants();
        foreach ($constants as $name => $constant) {
            $s->putValue($name, Value::from($constant));
        }

        $puns = get_defined_functions()["internal"];
        foreach ($puns as $pun) {
            $s->putValue($pun, new Pun($pun));
        }

        return $s;
    }

    /**
     * @return Scope
     *
     * for type check
     */
    public static function buildInitTypeScope()
    {
        $init = new Scope;

        $init->putValue("+", new Add());
        $init->putValue("-", new Sub());
        $init->putValue("*", new Mult());
        $init->putValue("/", new Div());

        $init->putValue("<", new Lt());
        $init->putValue("<=", new LtE());
        $init->putValue(">", new Gt());
        $init->putValue(">=", new GtE());
        $init->putValue("=", new Eq());
        $init->putValue("and", new And_());
        $init->putValue("or", new Or_());
        $init->putValue("not", new Not());
        $init->putValue("U", new UnionType());

        $init->putValue("true", Type::$BOOL);
        $init->putValue("false", Type::$BOOL);

        $init->putValue("Int", Type::$INT);
        $init->putValue("Bool", Type::$BOOL);
        $init->putValue("String", Type::$STRING);
        $init->putValue("Any", Value::$ANY);

        return $init;
    }
}