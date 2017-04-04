<?php

/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/4/1
 * Time: 下午10:39
 */


$ret42 = function() { return 42; };

echo (new Cont($ret42))->extract();
echo "\n";

echo Cont::pure(42)->extract();
echo "\n";



// 对于 Cont，箱子是形如   λk.kx    \lambda k.\,k\,x   的一层封装
//（接受一个 continuation，把「里面的东西」丢给他；数学上是一个双对偶空间）
// A cont monad of x is the double dual of x
class Cont
{
    private $run;

    public function __construct(callable $kkX)
    {
        $this->run = $kkX;
    }

    public function __invoke(...$args)
    {
        $run = $this->run;
        return $run(...$args);
    }

    // functor rules
    public function map(callable $f)
    {
        return new static(function ($kY) use ($f) {
            return $this(static function ($x) use ($f, $kY) {
                return $kY($f($x));
            });
        });
    }

    // pure : x -> Cont x
    public static function pure($x)
    {
        return new static(static function (callable $kX) use ($x) {
            return $kX($x);
        });
    }

    // ap : Cont x -> Cont (x -> y) -> Cont y
    public function ap(Cont $contXY)
    {
        return new static(function ($kY) use ($contXY) {
            return $contXY(function ($fXY) use ($kY) {
                return $this(static function ($x) use ($kY, $fXY) {
                    return $kY($fXY($x));
                });
            });
        });
    }

    // bind : Cont x -> (x -> Cont Y) -> Cont y
    public function bind($xContY)
    {
        return new static(function($kY) use($xContY) {
            return $this(static function($x) use($xContY, $kY){
                return $xContY($x)->run($kY);
            });
        });
    }

    // extract : Cont x -> x
    public function extract()
    {
        return $this(static function($x) {
            return $x;
        });
    }

    // extend : Cont x -> (Cont x -> y) -> Cont y
    public function extend(callable $contX2Y)
    {
        return new static(static function(callable  $kY) use($contX2Y) {
            return $kY($contX2Y($this));
        });
    }
}

// .net Task用的是comonad接口
// JS Promise 走的是 monad 接口
// 尽管Cont本身是monad也是comonad
// promise 能嵌套, 因为有结合律