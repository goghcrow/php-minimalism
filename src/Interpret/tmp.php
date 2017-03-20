<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/3/20
 * Time: 下午12:42
 */

//function car($pair)
//{
//    return $pair("car");
//}
//
//function cdr($pair)
//{
//    return $pair("cdr");
//}
//
//function cons($first, $rest)
//{
//    return function($cmd) use($first, $rest) {
//        if ($cmd === "car") {
//            return $first;
//        } else if ($cmd === "cdr") {
//            return $rest;
//        }
//        throw new \Exception();
//    };
//}
//
//
//
//class Pair
//{
//    private $a;
//    private $b;
//
//    public function __construct($a, $b)
//    {
//        $this->a = $a;
//        $this->b = $b;
//    }
//}
//
//
//$list = new Pair(1, new Pair(2, new Pair(3, new Pair(4, new Pair(5, null)))));
//function len($list)
//{
//    if($list->b == null){
//        return 1;
//    }
//    return 1+len($list->b);
//}

//function cons($a, $b) {
//    return [$a, $b];
//}
//
//function car($pair) {
//    return $pair[0];
//}
//
//function cdr($pair) {
//    return $pair[1];
//}


// $pair = cons(1, 2);
// echo car($pair); // 1
// echo cdr($pair); // 2



//ADT;
//interface;

// 1,2,3,4,5
//$list = cons(1, cons(2, cons(3, cons(4, cons(5, null)))));

//function len($list)
//{
//    if (cdr($list) === null) {
//        return 1;
//    }
//    return 1 + len(cdr($list));
//}
//
//function at($n) {
//
//}
//
//var_dump(len($list));

//exit;


//function compose(...$funs)
//{
//    return function($next = null) use($funs) {
//        if ($next === null) {
//            $next = function() { };
//        }
//
//        $i = count($funs);
//        while ($i--) {
//            $curr = $funs[$i];
//            $next = function() use($curr, $next)  {
//                $curr($next);
//            };
//        }
//
//        return $next();
//    };
//}
//
//
//// 统计时间
//function a($next)
//{
//    $start = microtime(true);
//    echo "before a\n";
//    $next();
//    echo "after a\n";
//    echo "cost: ",  microtime(true) - $start, "\n";
//}
//
//// 验证密码
//function b($next)
//{
//    echo "before b\n";
//    $next();
//    echo "after b\n";
//}
//
//// 业务逻辑
//function c()
//{
//    echo "hello c\n";
//}
//
//$fn = compose("a", "b", "c");
//$fn();
//exit;