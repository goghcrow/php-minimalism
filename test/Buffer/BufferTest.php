<?php

namespace Minimalism\Test;


use Minimalism\Buffer\MemoryBuffer;
use Minimalism\Buffer\StringBuffer;
use Minimalism\TestUtils;

require __DIR__ . "/../../src/Buffer/Buffer.php";
require __DIR__ . "/../../src/Buffer/MemoryBuffer.php";
require __DIR__ . "/../../src/Buffer/StringBuffer.php";
require __DIR__ . "/../../src/TestUtils.php";

$buffer = new StringBuffer();
$buffer->write("1234");
assert($buffer->read(1) === "1");
assert($buffer->__toString() === "234");
$buffer->write("56");
assert($buffer->__toString() === "23456");
assert($buffer->read(2) === "23");
$buffer->write("789");
assert($buffer->__toString() === "456789");
$buffer->prepend("123");
assert($buffer->__toString() === "123456789");

$buffer = new MemoryBuffer(5);
$buffer->write("1234");
assert($buffer->read(1) === "1");
assert($buffer->__toString() === "234");
$buffer->write("56");
assert($buffer->__toString() === "23456");
assert($buffer->writableBytes() === 1);
assert($buffer->capacity() === 10);
assert($buffer->read(2) === "23");
assert($buffer->prependableBytes() === 6);
assert($buffer->writableBytes() === 1);
$buffer->write("789");
assert($buffer->prependableBytes() === 4);
assert($buffer->readableBytes() === 6);
assert($buffer->writableBytes() === 0);
assert($buffer->capacity() === 10);

$buffer = new MemoryBuffer(8);
$buffer->write("5678");
$buffer->prepend("34");
assert($buffer->__toString() === "345678");
assert($buffer->prependableBytes() === 2);
$buffer->write(9);
assert($buffer->prependableBytes() === 4);
assert($buffer->writableBytes() === 3);
//echo $buffer->capacity();
exit;


// ObjectPool::create(new Binary(new MemoryBuffer(8192)), 3000);

// 性能测试: !!! 关闭xdebug

ini_set("memory_limit", "256M");

TestUtils::cost(function() {
    $buffer = new MemoryBuffer(1024);
    for ($i = 0; $i < 1000000; $i++) {
        $buffer->write((string)$i);
    }
    $str = $buffer->readFull();
    // echo substr($str, -20), "\n";

    // 8MB 40KB 476Byte
    // echo \TestUtils::formatBytes($buffer->capacity()), "\n";
});

TestUtils::cost(function() {
    $buffer = [];
    for ($i = 0; $i < 1000000; $i++) {
        $buffer[] = $i;
    }
    $str = implode("", $buffer);
    // echo substr($str, -20), "\n";
});

TestUtils::cost(function() {
    $str = "";
    for ($i = 0; $i < 1000000; $i++) {
        $str .= $i;
    }
    // echo substr($str, -20), "\n";
});


// !!! swoole buffer 没有通过 emalloc 申请内存 ?!

// result
/*
my buffer
============================================================
Cost Summary:
elapsed seconds               1s 343ms 338.96636962891us
memory usage                  5MB 649KB 720Byte
    emalloc memory            575KB 256Byte
    malloc memory             768KB
    emalloc peak memory       6MB 194KB 200Byte
    malloc peak memory        6MB 512KB
============================================================

cache by array
============================================================
Cost Summary:
elapsed seconds               373ms 433.11309814453us
memory usage                  143MB 338KB 344Byte
    emalloc memory            588KB 632Byte
    malloc memory             1MB 256KB
    emalloc peak memory       143MB 922KB 888Byte
    malloc peak memory        144MB 512KB
============================================================

string concat
============================================================
Cost Summary:
elapsed seconds               155ms 877.11334228516us
memory usage                  143MB 324KB 912Byte
    emalloc memory            602KB 24Byte
    malloc memory             1MB 256KB
    emalloc peak memory       143MB 922KB 888Byte
    malloc peak memory        144MB 512KB
============================================================
*/