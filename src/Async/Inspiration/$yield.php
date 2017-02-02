<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/2
 * Time: 下午7:56
 */

namespace Minimalism\Async\Inspiration;


/*
//http://blog.zhaojie.me/2010/06/code-for-fun-iterator-generator-yield-in-javascript-answer-1-yield-and-yieldseq.html

// 实现yield的链表结构
class Iter
{
    public $value;

    private $iter;

    private $continuation;

    public static function _yield($value, callable $continuation = null)
    {
        $self = new self;
        $self->value = $value;
        $self->continuation = $continuation;
        return $self;
    }

    public static function _yieldSeq($iter, callable $continuation = null)
    {
        if ($continuation === null) {
            return $iter;
        }

        if ($iter === null) {
            return $continuation();
        }


        $self = new self;
        $self->iter = $iter;
        $self->value = $iter->value;

        $self->continuation = $continuation;
        return $self;

    }

    public function next()
    {
        if ($continuation = $this->continuation) {
            return $continuation();
        } else {
            return null;
        }
    }
}


function _yield($value, callable $continuation = null)
{
    return new _node($value, $continuation);
}


function numSeq($n = 0)
{
    return _yield($n                    , function() use($n) {
    return yieldSeq(numSeq($n + 1))       ;});
}

function _yieldSeq($iter, $rest)
{

}

for ($iter = numSeq(0); $iter !== null; $iter = $iter->next()) {
    echo $iter->value, "\n";
}

*/