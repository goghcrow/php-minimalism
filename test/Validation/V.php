<?php

namespace Minimalism\Test\Validation;

use Minimalism\Validation\P;
use Minimalism\Validation\V;
use Minimalism\Validation\VType\VNil;

require __DIR__ . "/../../vendor/autoload.php";

try {
    $ex = null;
    V::of(null)->toInt()->orThrow(new \Exception("ex"));
    assert(false);
} catch (\Exception $ex) {}
assert($ex && $ex->getMessage() == "ex");


try {
    $ex = null;
    V::ofObject(new \stdClass())->prop("name")->orThrow();
    assert(false);
} catch (\Exception $ex) {}
assert($ex && $ex->getMessage() == "Property name Not Exist In Object stdClass");


try {
    $ex = null;
    V::of(null)->toInt()->orThrow();
    assert(false);
} catch (\Exception $ex) {}
assert($ex && $ex->getMessage() == "Invalid Int");



assert(V::of("hello")->notEmpty()->orElse("xxx") === "hello");
assert(V::of(0)->notEmpty()->orElse("xxx") === "xxx");
assert(V::of("")->notEmpty()->orElse("xxx") === "xxx");
assert(V::of([])->notEmpty()->orElse("xxx") === "xxx");
assert(V::of(null)->notEmpty()->orElse("xxx") === "xxx");
assert(V::of(false)->notEmpty()->orElse("xxx") === "xxx");

assert(V::of("hello")->vAssert(P::notEmpty())->orElse("xxx") === "hello");
assert(V::of(0)->vAssert(P::notEmpty())->orElse("xxx") === "xxx");
assert(V::of("")->vAssert(P::notEmpty())->orElse("xxx") === "xxx");
assert(V::of([])->vAssert(P::notEmpty())->orElse("xxx") === "xxx");
assert(V::of(null)->vAssert(P::notEmpty())->orElse("xxx") === "xxx");
assert(V::of(false)->vAssert(P::notEmpty())->orElse("xxx") === "xxx");


$add1 = function($a) { return $a + 1; };
assert(V::of("hello")->vMap("mb_strlen")->toInt()->orElse(-1) === 5);
assert(V::ofArray([1,2,3])->map($add1)->get() === [2,3,4]);

assert(V::ofInt(1)->orElse(2) === 1);
assert(V::ofInt("1x")->orElse(2) === 2);
assert(V::ofInt("abc")->orElse(2) === 2);
assert(V::ofInt("01")->orElse(2) === 2);
assert(V::ofInt("01", FILTER_FLAG_ALLOW_OCTAL)->orElse(2) === 1);
assert(V::ofInt("0x1")->orElse(2) === 2);
assert(V::ofInt("0xa", FILTER_FLAG_ALLOW_HEX)->orElse(2) === 10);

assert(V::ofInt(15)->range(10, 14)->orElse(0) === 14);
assert(V::ofInt("xxx")->range(10, 14)->orElse(0) === 0);

assert(V::ofInt(15)->min(10)->get() === 15);
assert(V::ofInt(15)->min(15)->get() === 15);
assert(V::ofInt(15)->min(16)->get() === 16);
assert(V::ofInt("xxx")->min(16)->orElse(0) === 0);

assert(V::ofInt(15)->max(16)->get() === 15);
assert(V::ofInt(15)->max(15)->get() === 15);
assert(V::ofInt(15)->max(14)->get() === 14);
assert(V::ofInt("xxx")->max(16)->orElse(0) === 0);

assert(V::ofInt(10)->between(10, 15)->get() === 10);
assert(V::ofInt(9)->between(10, 15)->orElse(0) === 0);
assert(V::ofInt("xxx")->between(10, 15)->orElse(0) === 0);

assert(V::ofInt(10)->ge(10)->le(15)->get() === 10);
assert(V::ofInt(10)->ge(11)->le(15)->orElse(1) === 1);
assert(V::ofInt(15)->ge(11)->le(15)->orElse(1) === 15);
assert(V::ofInt(15)->ge(11)->le(14)->orElse(1) === 1);
assert(V::ofInt(15)->gt(15)->orElse(1) === 1);
assert(V::ofInt(15)->gt(14)->orElse(1) === 15);
assert(V::ofInt(15)->lt(14)->orElse(1) === 1);
assert(V::ofInt(15)->lt(16)->orElse(1) === 15);
assert(V::ofInt("xxx")->lt(16)->orElse(1) === 1);



assert(V::ofArray([1,2,3])->count()->lt(3)->valid() === false);
assert(V::ofArray([1,2,3])->count()->lt(4)->valid() === true);

assert(V::ofArray(["a" => ["b" => 100]])
        ->key("a")
        ->toArray()
        ->key("b")
        ->toInt()
        ->orElse(10) === 100);
assert(V::ofArray(["a" => ["b" => "hello"]])
        ->key("a")
        ->toArray()
        ->key("b")
        ->toInt()
        ->orElse(10) === 10);
assert(V::ofArray(["a" => ["x" => []]])
        ->key("a")
        ->toArray()
        ->key("b")
        ->toArray()
        ->key("c")
        ->toInt()
        ->orElse(1000) === 1000);

assert(V::ofArray(["a" => ["b" => 100]])->visit("a.b")->toInt()->orElse(10) === 100);
assert(V::ofArray(["a" => ["b" => 100]])->visit("a.c")->toInt()->orElse(10) === 10);
assert(V::ofArray(["a" => ["b" => "hello"]])->visit("a.b")->toInt()->orElse(10) === 10);

assert(V::ofArray(range(1, 5))->contains(1)->get());
assert(V::ofArray(range(1, 5))->contains(6)->get() === false);
assert(V::ofArray(range(1, 5))->contains("1", true)->get() === false);


assert(V::ofArray([1,2, "3"])->all("is_int")->get() === false);
assert(V::ofArray([1,2, "3"])->any("is_int")->get() === true);
assert(V::ofArray([])->any("is_int")->get() === false);
assert(V::ofArray([])->all("is_int")->get() === false);

$allInt = P::ofArray()->all("is_int");
$anyInt = P::ofArray()->any("is_int");
$allOdd = P::ofArray()->all(function($v) {
    return $v % 2 === 0;
});
$arr = [2, 4, "8"];
assert(V::ofArray($arr)->vAssert($allInt)->orElse([]) === []);
assert(V::ofArray($arr)->vAssert($anyInt)->orElse() === $arr);
assert(V::ofArray($arr)->vAssert($anyInt)->vAssert($allOdd)->orElse() === $arr);



assert(V::ofNil()->orElse(1) === 1);

assert(V::ofBool(1)->orElse(null) === true);
assert(V::ofBool(true)->orElse(null) === true);
assert(V::ofBool("1")->orElse(null) === true);
assert(V::ofBool("true")->orElse(null) === true);
assert(V::ofBool("yes")->orElse(null) === true);
assert(V::ofBool("on")->orElse(null) === true);

assert(V::ofBool("0")->orElse(null) === false);
assert(V::ofBool("false")->orElse(null) === false);
assert(V::ofBool("off")->orElse(null) === false);
assert(V::ofBool("no")->orElse(null) === false);
assert(V::ofBool("")->orElse(null) === false);
assert(V::ofBool(0)->orElse(null) === false);
assert(V::ofBool(null)->orElse(null) === false);

assert(V::ofBool("other")->orElse(null) === null);
assert(V::ofBool(123)->orElse(null) === null);

assert(V::ofString("1")->orElse("hello") === "1");
assert(V::ofString(1)->orElse("hello") === "1");
assert(V::ofString("")->orElse("hello") === "");
assert(V::ofString("")->get() === "");
assert(V::ofString(null)->get() === "");
assert(V::ofString(false)->get() === "");
assert(V::ofString(true)->get() === "1");
assert(V::ofString([])->orElse("hello") === "hello");
assert(V::ofString(new \stdClass())->orElse("hello") === "hello");


assert(V::ofString("hello")->vAssert(P::strlen()->between(3, 8))->orElse("hi") === "hello");
assert(V::ofString("hello")->vAssert(P::strlen()->between(5, 8))->orElse("hi") === "hello");
assert(V::ofString("hello")->vAssert(P::strlen()->between(6, 8))->orElse("hi") === "hi");
assert(V::ofString("hello")->vAssert(P::strlen()->between(3, 5))->orElse("hi") === "hello");
assert(V::ofString("hello")->vAssert(P::strlen()->between(3, 4))->orElse("hi") === "hi");

assert(V::ofString("hello")->vAssert(P::strlen()->lt(6))->orElse("hi") === "hello");
assert(V::ofString("hello")->vAssert(P::strlen()->lt(5))->orElse("hi") === "hi");
assert(V::ofString("hello")->vAssert(P::strlen()->le(5))->orElse("hi") === "hello");

assert(V::ofString("hello")->vAssert(P::strlen()->gt(4))->orElse("hi") === "hello");
assert(V::ofString("hello")->vAssert(P::strlen()->gt(5))->orElse("hi") === "hi");
assert(V::ofString("hello")->vAssert(P::strlen()->ge(5))->orElse("hi") === "hello");

assert(V::ofString("hello")->vAssert(P::strlen()->lt(6)->gt(4))->orElse("hi") === "hello");
assert(V::ofString("hello")->vAssert(P::strlen()->lt(6)->gt(5))->orElse("hi") === "hi");

assert(V::ofString("hello")->vAssert(P::ofString()->len()->between(3, 8))->orElse("hi") === "hello");
assert(V::ofString("hello")->vAssert(P::ofString()->len()->between(5, 8))->orElse("hi") === "hello");
assert(V::ofString("hello")->vAssert(P::ofString()->len()->between(6, 8))->orElse("hi") === "hi");
assert(V::ofString("hello")->vAssert(P::ofString()->len()->between(3, 5))->orElse("hi") === "hello");
assert(V::ofString("hello")->vAssert(P::ofString()->len()->between(3, 4))->orElse("hi") === "hi");

assert(V::ofString("hello")->vAssert(P::ofString()->len()->lt(6))->orElse("hi") === "hello");
assert(V::ofString("hello")->vAssert(P::ofString()->len()->lt(5))->orElse("hi") === "hi");
assert(V::ofString("hello")->vAssert(P::ofString()->len()->le(5))->orElse("hi") === "hello");

assert(V::ofString("hello")->vAssert(P::ofString()->len()->gt(4))->orElse("hi") === "hello");
assert(V::ofString("hello")->vAssert(P::ofString()->len()->gt(5))->orElse("hi") === "hi");
assert(V::ofString("hello")->vAssert(P::ofString()->len()->ge(5))->orElse("hi") === "hello");

assert(V::ofString("hello")->vAssert(P::ofString()->len()->lt(6)->gt(4))->orElse("hi") === "hello");
assert(V::ofString("hello")->vAssert(P::ofString()->len()->lt(6)->gt(5))->orElse("hi") === "hi");

assert(V::ofString("###hello###")->trim('#')->get() === "hello");
assert(V::ofString("###hello###")->vMap(function($var) {return trim($var, "#");})->get() === "hello");
assert(V::ofString("###hello###")->vMap("trim", "#")->get() === "hello");

assert(V::ofString("Hello")->len()->get() === 5);
assert(V::ofString("Hello")->sizeof()->get() === 5);

assert(V::ofString("中文")->len()->get() === 2);
assert(V::ofString("中文")->sizeof()->get() === 6);

assert(V::ofString("hello")->vAssert(P::ofString()->startWith("he"))->orElse(false) == true);
assert(V::ofString("hello")->vAssert(P::ofString()->startWith("xx"))->orElse(false) == false);
assert(V::ofString("hello")->vAssert(P::ofString()->startWith("he"))->orElse("world") === "hello");
assert(V::ofString("hello")->vAssert(P::ofString()->startWith("xx"))->orElse("world") === "world");

assert(V::ofString("hello")->vAssert(P::ofString()->endWith("lo"))->orElse("world") === "hello");
assert(V::ofString("hello")->vAssert(P::ofString()->endWith("xx"))->orElse("world") === "world");
assert(V::ofString("hello")->vAssert(P::ofString()->endWith("lo"))->orElse(false) == true);
assert(V::ofString("hello")->vAssert(P::ofString()->endWith("xx"))->orElse(false) == false);


assert(V::of("hello")->beEmpty()->orElse("world") === "world");

$ret = V::ofArray([5, 4, 3, 2, 1])->vMap(function($arr) { sort($arr); return $arr;})->get();
assert($ret === range(1, 5));

assert(V::ofArray(range(1,9))->join(",")->get() === "1,2,3,4,5,6,7,8,9");
assert(V::ofArray(range(1,9))->join(",")->explode(",")->map("intval")->get() === range(1,9));

assert(V::ofArray([1,2,3])->vAssert(P::count()->eq(3))->orElse([]) === [1,2,3]);
assert(V::ofArray([1,2,3])->vAssert(P::count()->eq(4))->orElse([]) === []);

/** @noinspection PhpUndefinedMethodInspection */
$ret = V::ofArray([[1,2,3], [4,5,6], [7,8,9]])->toStream()->flatten()->toArray();
assert($ret === range(1,9));

/** @noinspection PhpUndefinedMethodInspection */
$ret = V::ofArray(null)->toStream()->flatten()->toArray();
assert($ret instanceof VNil);




class InvokeClass {
    public function __invoke($name) {
        return "hello $name";
    }
}

$call = V::ofCallable(new InvokeClass)->orElse(1);
assert($call("xiaofeng") === "hello xiaofeng");
