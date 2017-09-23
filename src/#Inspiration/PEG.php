<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/2
 * Time: 下午7:53
 */

namespace Minimalism\A\Inspiration;

//@see https://typeof.net/2014/m/an-example-about-control-flow-abstraction-parser-expression-grammar.html

// 流程抽象

class Rule
{
}



// 终结规则
class FinalRule extends Rule
{
    public $pattern;

    public function __construct($pattern)
    {
        $this->pattern = $pattern;
    }

    public function match($state, &$newState, &$result)
    {
        $patternLen = strlen($this->pattern);
        if (substr($state, 0, $patternLen) === $this->pattern) {
            $newState = strval(substr($state, $patternLen));
            $result = $this->pattern;
            return true;
        } else {
            return false;
        }
    }
}

// 顺序组合
class SequenceRule extends Rule
{
    public $front;
    public $rear;

    public function __construct($front, $rear)
    {
        $this->front= $front;
        $this->rear = $rear;
    }
}

// 有序并
class ChooseRule extends Rule
{
    public $superior;
    public $inferior;

    public function __construct($superior, $inferior)
    {
        $this->superior = $superior;
        $this->inferior = $inferior;
    }
}



/**
 * Parser Expression Grammar
 * Parser Generator CPS Implemented
 * @param Rule $rule
 * @param string $state
 * @param callable $onmatch onmatch(state, result)
 * @param callable $onfail onfail(state)
 * @return mixed
 */
function match(Rule $rule = null, $state, callable $onmatch, callable $onfail)
{
    if ($rule instanceof FinalRule) {
        if ($rule->pattern === null) {
            return $onmatch($state, "");
        } else if ($rule->match($state, $newState, $result)) {
            return $onmatch($newState, $result);
        } else {
            return $onfail("Expected $rule->pattern got $state");
        }
    } else if ($rule instanceof SequenceRule) {
        return match($rule->front, $state, function($state1, $result1) use($rule, $onmatch, $onfail) {
            return match($rule->rear, $state1, function($state2, $result2) use($result1, $onmatch, $onfail) {
                return $onmatch($state2, [$result1, $result2]);
            }, $onfail);
        }, $onfail);
    } else if ($rule instanceof ChooseRule) {
        return match($rule->superior, $state, $onmatch, function() use($rule, $state, $onmatch, $onfail) {
            return match($rule->inferior, $state, $onmatch, $onfail);
        });
    } else {
        return $onmatch($state, null);
    }
}


//class Func
//{
//    public $name;
//    public $fun;
//    public function __construct($name, callable $fun) {
//        $this->name = $name;
//        $this->fun = $fun;
//    }
//    public function __invoke(...$args)
//    {
//        $fun = $this->fun;
//        return $fun(...$args);
//    }
//}
//
//function fun($name, callable $fun)
//{
//    return new Func($name, $fun);
//}
//
//// 允许参数为  null|Rule, PHP 可以这样表示可空对象
//function match(Rule $rule = null, $state, callable $onmatch, callable $onfail)
//{
//    if ($rule instanceof FinalRule) {
//        if ($rule->pattern === null) {
//            return $onmatch($state, "");
//        } else if ($rule->match($state, $newState, $result)) {
//            return $onmatch($newState, $result);
//        } else {
//            // throw new \Exception("Expected $rule->pattern got $state");
//            return $onfail("Expected $rule->pattern got $state");
//        }
//    } else if ($rule instanceof SequenceRule) {
//        return match($rule->front, $state, fun("seq-front-onmatch", function($state1, $result1) use($rule, $onmatch, $onfail) {
//            return match($rule->rear, $state1, fun("seq-read-onmatch", function($state2, $result2) use($result1, $onmatch, $onfail) {
//                return $onmatch($state2, [$result1, $result2]);
//            }), $onfail);
//        }), $onfail);
//    } else if ($rule instanceof ChooseRule) {
//        return match($rule->superior, $state, $onmatch, fun("choose-on-fain", function($state1) use($rule, $state, $onmatch, $onfail) {
//            return match($rule->inferior, $state, $onmatch, $onfail);
//        }));
//    } else {
//        return $onmatch($state, null);
//    }
//}

// TODO 括号不匹配工作不正常
//match($brackets, "(()abc", fun("onmatch", function($state, $result) {
//    echo "rest: $state\n";
//    echo "\n\n";
//
//    var_dump($result);
//    print_r($result);
////    echo json_encode($result, JSON_PRETTY_PRINT);
//}), fun("onfail", function($s) {
//    echo "onfail: $s\n";
//}));



function choose($superior, $inferior)
{
    return new ChooseRule($superior, $inferior);
}

function seq($front, $rear)
{
    return new SequenceRule($front, $rear);
}

function _($pattern)
{
    return new FinalRule($pattern);
}

//$brackets = choose(
//    seq(
//        _("("),
//        seq(
//            null,
//            _(")")
//        )),
//    _(null));



//// Choose(Seq('(', Seq(null, ')')), '')
$brackets = new ChooseRule(
    new SequenceRule(
        new FinalRule('('),
        new SequenceRule(
            null,
            new FinalRule(')')
        )
    ),
    new FinalRule("")
);

// 递归, 用来表示 brackets 内部 仍然可以是brackets
$brackets->superior->rear->front = $brackets;

match($brackets, "(())", function($state, $result) {
    echo json_encode($result, JSON_PRETTY_PRINT);
}, function($s) {
    echo "onfail: $s\n";
});

