<?php

namespace Minimalism\Test\Stream;

use iter;
use Minimalism\Stream\Stream;

require __DIR__ . "/../../src/Stream/Stream.php";


$add1 = function($a) { return $a + 1; };
$isEven = function($a) { return $a % 2 === 0; };
$isOdd = function($a) { return $a % 2 !== 0; };

$pp = function($v, $k) { echo "$k => $v"/*, PHP_EOL*/; };
$var_dump_iter = Stream::create()->apply(function($v) { var_dump($v); });



//////////////////////////////////////////////////////////////////////////////
// 1. 如何创建一个Stream实例

$array = range(0, 5);
assert(Stream::of($array)->__toString() === 'Stream(0, 1, 2, 3, 4, 5)');

$generator = function() {
    for($i = 0; $i < 6; $i++) yield $i;
};
assert(Stream::of($generator())->__toString() === 'Stream(0, 1, 2, 3, 4, 5)');

$arrayIter = new \ArrayIterator($array);
assert(Stream::of($arrayIter)->__toString() === 'Stream(0, 1, 2, 3, 4, 5)');

$rwGen = iter\callRewindable($generator);
assert(Stream::of($rwGen)->__toString() === 'Stream(0, 1, 2, 3, 4, 5)');

assert(Stream::from(0, 1, 2, 3, 4, 5)->__toString() === 'Stream(0, 1, 2, 3, 4, 5)');

assert(Stream::from([0, 1], [2, 3], [4, 5])->flatten()->__toString() === 'Stream(0, 1, 2, 3, 4, 5)');

assert(Stream::from($array, $generator(), $arrayIter, $rwGen)->flatten()->__toString() ===
    'Stream(0, 1, 2, 3, 4, 5, 0, 1, 2, 3, 4, 5, 0, 1, 2, 3, 4, 5, 0, 1, 2, 3, 4, 5)');




// example

//apply////////////////////////////////////////////////////////////////////////////

ob_start();
Stream::of(iter\range(0, 10))->apply(function($v) { echo $v; });
assert(ob_get_clean() === '012345678910');

//mapKeys//keys//////////////////////////////////////////////////////////////////////////

$itoa = function($v) {
    return chr(ord('a') + $v);
};

$list = Stream::range(1, 3)->mapKeys($itoa)->keys()->toArray();
assert($list === ["a","b","c"]);

$list = Stream::range(1, 3)->mapKeys($itoa)->toArrayWithKeys();
assert($list === [
    'a' => 1,
    'b' => 2,
    'c' => 3,
]);

//////////////////////////////////////////////////////////////////////////////

$iter_to_alpha = Stream::create()->map($itoa)->toArray();
$list = $iter_to_alpha(iter\range(0, 2));
// echo Stream::of($list), PHP_EOL;
assert($list === ['a', 'b', 'c']);

//reduce////////////////////////////////////////////////////////////////////////////

$a = Stream::range(1, 10)->reduce(iter\fn\operator("+"), 100);
$b = Stream::range(1, 10)->reduce(iter\fn\operator("+"), 0);
assert($a === $b + 100);

$iter_sum = Stream::create()->reduce(iter\fn\operator("+"), 0);
assert($iter_sum(iter\range(1, 100)) === 5050);

////map//reductions////////////////////////////////////////////////////////////////////////

$a = Stream::range(1, 4)->reductions(iter\fn\operator("+"), 100)->toArray();
$b = Stream::range(1, 4)->reductions(iter\fn\operator("+"), 0)->map(iter\fn\operator("+", 100))->toArray();
assert($a === $b);

//peek//map//any//all//////////////////////////////////////////////////////////////////////

assert(Stream::range(1, 10)->all("is_int"));

$iter_all_int = Stream::create()->all("is_int");
assert($iter_all_int([1,2,3]));
assert($iter_all_int([1, "a"]) === false);

ob_start();
$addSharp = function($v) { return "#$v"; };
$ret = Stream::range(1, 10)->map($addSharp)->peek($pp)->any("is_int");
assert($ret === false);
assert(ob_get_clean() === '0 => #11 => #22 => #33 => #44 => #55 => #66 => #77 => #88 => #99 => #10');

$iter_any_int = Stream::create()->any("is_int");
assert($iter_any_int(["a", "b", 1]));
assert($iter_any_int(["a", "b"]) === false);


$ret = Stream::of([1,2,3,"str"])->any("is_string");
assert($ret);

$ret = Stream::of([1,2,3,"str"])->all("is_string");
assert($ret === false);

//findFirst////////////////////////////////////////////////////////////////////////////

ob_start();
$geFive = function($v) { return $v === 5;};
$ret = Stream::range(1, 10)->peek($pp)->findFirst($geFive);
assert($ret === 5);
assert(ob_get_clean() === "0 => 11 => 22 => 33 => 44 => 5");

$iter_find_first = Stream::create()->findFirst($geFive);
$ret = $iter_find_first(iter\range(1, 10));
assert($ret === 5);

//flatten////////////////////////////////////////////////////////////////////////////

$a = Stream::from(iter\range(1, 4), iter\range(5, 7), iter\range(8, 10))->flatten()->toArray();
$b = Stream::range(1, 10)->toArray();
assert($a === $b);

$ret = Stream::from([1], [2,3, [4, [5]]])->flatten(1)->toArray();
assert($ret === [1,2,3,[4,[5]]]);

$ret = Stream::from([1], [2,3, [4, [5]]])->flatten(2)->toArray();
assert($ret === [1,2,3, 4, [5]]);

$ret = Stream::from([1], [2,3, [4, [5]]])->flatten(3)->toArray();
assert($ret === [1,2,3, 4, 5]);


//flatMap//chunk//////////////////////////////////////////////////////////////////////////


$a = Stream::from(iter\range(1, 4), iter\range(5, 7), iter\range(8, 10))->flatMap($add1)->toArray();
$b = Stream::range(2, 11)->toArray();
assert($a === $b);

$ret = Stream::range(1, 5)->chunk(2)->toArray();
assert($ret === [[1,2],["2"=>3,"3"=>4],["4"=>5]]);


//reindex//flip//////////////////////////////////////////////////////////////////////////

$reindex = function($v) { return "#$v"; };
$ret = Stream::range(1, 3)->reindex($reindex)->flip()->toArrayWithKeys();
assert($ret === ["1"=>"#1","2"=>"#2","3"=>"#3"]);

//join////////////////////////////////////////////////////////////////////////////

$ret = Stream::range(1, 3)->join(", ");
assert($ret === "1, 2, 3");

//count////////////////////////////////////////////////////////////////////////////

$ret = Stream::range(1, 10)->count();
assert($ret === 10);

//repeat//join//////////////////////////////////////////////////////////////////////////

$ret = Stream::repeat("X", 10)->join();
assert($ret === str_repeat("X", 10));

//slice////////////////////////////////////////////////////////////////////////////

$ret = Stream::range(1, 5)->slice(0, 3)->toArray();
assert($ret === [1,2,3]);

//take////////////////////////////////////////////////////////////////////////////

$ret = Stream::range(1, 5)->take(3)->toArray();
assert($ret === [1,2,3]);


//drop////////////////////////////////////////////////////////////////////////////

$ret = Stream::range(1, 5)->drop(3)->toArrayWithKeys();
assert($ret === ["3"=>4, "4"=>5]);

//zip////////////////////////////////////////////////////////////////////////////

$ret = Stream::range(1,3)->zip([4,5,6], [7,8,9])->toArray();
assert($ret === [[1,4,7],[2,5,8],[3,6,9]]);


//chain//join//////////////////////////////////////////////////////////////////////////

$ret = Stream::range(1, 3)->chain(iter\range(4,6), iter\range(7,9))->join();
assert($ret === "123456789");

//product////////////////////////////////////////////////////////////////////////////

$ret = Stream::range(1, 2)->product(iter\rewindable\range(3, 4))->toArray();
assert($ret === [[1,3],[1,4],[2,3],[2,4]]);

//zip//zipKey//zipValue////////////////////////////////////////////////////////////////////////

$a = Stream::of(range("a", "c"))->zipValue(iter\range(1, 3))->toArrayWithKeys();
$b = Stream::range(1, 3)->zipKey(range("a", "c"))->toArrayWithKeys();
assert($a === $b);

//where//reduce//////////////////////////////////////////////////////////////////////////

$iter_even_sum = Stream::create()->where($isEven)->reduce(iter\fn\operator("+"));
assert($iter_even_sum([1,2,4,5,7,8]) === 14);


//takeWhile////////////////////////////////////////////////////////////////////////////
$ret = Stream::from(3, 1, 4, -1, 5)->takeWhile(iter\fn\operator('>', 0))->toArray();
assert($ret === [3, 1, 4]);

//dropWhile////////////////////////////////////////////////////////////////////////////
$ret = Stream::from(3, 1, 4, -1, 5)->dropWhile(iter\fn\operator('>', 0))->toArray();
assert($ret === [-1, 5]);

//filter////////////////////////////////////////////////////////////////////////////

$city_list = [
    ["id" => 1, "city" => "beijing", "score" => 1.1],
    ["id" => 2, "city" => "shanghai", "score" => 2.3],
    ["id" => 3, "city" => "chengdu", "score" => 3.1],
    ["id" => 4, "city" => "tianjin", "score" => 0.5]
];

$scopeGt1 = function($a) { return $a["score"] > 1; };
$iter_index_city = Stream::create()->filter($scopeGt1)->map(iter\fn\index("city"))->toArray();
assert($iter_index_city($city_list) === ['beijing', 'shanghai', 'chengdu']);