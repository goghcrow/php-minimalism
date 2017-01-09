<?php

namespace Minimalism\Test;

use Minimalism\PatternMatch;

require __DIR__ . "/../src/PatternMatch.php";


class PatternMatchTest
{
    private function test_one_arg($arg1 = "hello")
    {
        echo "hello null\n";
        $this->test("world", []);
    }

    private function test_array($arg1 = "hello", array $other)
    {
        echo "hello array\n";
    }

    private function test_ojb($arg1 = "hello", \stdClass $other)
    {
        echo "hello obj\n";
    }

    // 强类型优先定义, 弱类型定义到后面
    private function test_mix($arg1 = "hello", $mix)
    {
        echo "hello mix\n";
    }

    private function test_d($arg1 = "world", array $other)
    {
        echo "world\n";
    }

    /**
     * 默认方法
     */
    private function test_()
    {
        echo "defualt\n";
    }

    public function test(...$args)
    {
        $pm = PatternMatch::make($this, __FUNCTION__);
        return $pm(...$args);
    }
}


$pmTest = new PatternMatchTest();
ob_start();
$pmTest->test();
$pmTest->test("hello", 1);
$pmTest->test("hello", []);
$pmTest->test("hello", new \stdClass());
$pmTest->test("hello");
assert(ob_get_clean() === <<<BUFFER
defualt
hello mix
hello array
hello obj
hello null
world

BUFFER
);



class PatternMatchStaticTest
{
    private static function test_one_arg($arg1 = "hello")
    {
        echo "hello null\n";
    }

    private static function test_array($arg1 = "hello", array $other)
    {
        echo "hello array\n";
    }

    private static function test_ojb($arg1 = "hello", \stdClass $other)
    {
        echo "hello obj\n";
    }

    // 强类型优先定义, 弱类型定义到后面
    private static function test_mix($arg1 = "hello", $mix)
    {
        echo "hello mix\n";
    }

    private static function test_d($arg1 = "world", array $other)
    {
        echo "world\n";
    }

    /**
     * 默认方法
     */
    private static function test_()
    {
        echo "defualt\n";
    }

    public static function test(...$args)
    {
        $pm = PatternMatch::make(static::class, __FUNCTION__);
        return $pm(...$args);
    }
}

ob_start();
PatternMatchStaticTest::test();
PatternMatchStaticTest::test("hello", 1);
PatternMatchStaticTest::test("hello", []);
PatternMatchStaticTest::test("hello", new \stdClass());
assert(ob_get_clean() === <<<BUFFER
defualt
hello mix
hello array
hello obj

BUFFER
);
