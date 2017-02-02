<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/2
 * Time: 下午7:53
 */

namespace Minimalism\Async\Inspiration;

//@see https://typeof.net/2014/m/an-example-about-control-flow-abstraction-parser-expression-grammar.html

// 流程抽象

class Rule
{
}

class FinalRule extends Rule
{
    public $pattern;

    public function __construct($pattern)
    {
        $this->pattern = $pattern;
    }
}

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


// TODO 写注释 !!!

/**
 * Parser Expression Grammar
 * Parser Generator (CPS实现)
 * @param Rule $rule
 * @param string $state
 * @param callable $onmatch onmatch(state, result)
 * @param callable $onfail onfail(state)
 * @return mixed
 */
function match($rule, $state, callable $onmatch, callable $onfail)
{
    if ($rule instanceof FinalRule) {
        if ($rule->pattern === null) {
            return $onmatch($state, "");
        } else if (/*TODO 替换匹配规则*/substr($state, 0, strlen($rule->pattern)) === $rule->pattern) {
            return $onmatch(substr($state, strlen($rule->pattern)) ?: "", $rule->pattern);
        } else {
            // TODO echo "Expected $rule->pattern\n";
            return $onfail("Expected $rule->pattern");
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



// Choose(Seq('(', Seq(null, ')')), '')
$brackets = new ChooseRule(
    new SequenceRule(
        new FinalRule('('),
        new SequenceRule(
            null,
            new FinalRule(')'))),
    null
);

// 递归, 用来表示 brackets 内部 仍然可以是brackets
$brackets->superior->rear->front = $brackets;

// TODO 括号不匹配工作不正常
match($brackets, "((()))abc", function($state, $result) {
    echo "rest: $state\n";
    echo json_encode($result, JSON_PRETTY_PRINT);
}, function($s) {
    echo "onfail: $s\n";
});
