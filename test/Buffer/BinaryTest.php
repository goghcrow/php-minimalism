<?php

namespace Minimalism\Test\Buffer;


use Minimalism\Buffer\BinaryStream;
use Minimalism\Buffer\MemoryBuffer;
use Minimalism\Buffer\StringBuffer;


require __DIR__ . "/../../src/Buffer/Buffer.php";
require __DIR__ . "/../../src/Buffer/MemoryBuffer.php";
require __DIR__ . "/../../src/Buffer/StringBuffer.php";
require __DIR__ . "/../../src/Buffer/BinaryStream.php";


$buf = new MemoryBuffer();
//$buf = new StringBuffer();
assert($buf->readableBytes() === 0);
//assert($buf->writableBytes() === MemoryBuffer::kInitialSize);
//assert($buf->prependableBytes() === MemoryBuffer::kCheapPrepend);


$buf->write("\r\n");
$buf->write("HELLO\r\n");
$buf->write("WORLD\r\n");

assert($buf->readLine() === "");
assert($buf->readLine() === "HELLO");
assert($buf->readLine() === "WORLD");
assert($buf->readLine() === false);


$buf->write("HELLO");
assert($buf->peek(5) === "");
assert($buf->peek(0, 5) === "HELLO");
assert($buf->peek(0, 4) === "HELL");
assert($buf->peek(1, 4) === "ELLO");

// ========================================================

$bin = new BinaryStream(new MemoryBuffer());

$bin->writeUInt8(12);
$bin->writeUInt16BE(24);
$bin->writeUInt16LE(24);
$bin->writeUInt32BE(1022);
$bin->writeUInt32LE(1022);

$bin->writeUInt64BE(PHP_INT_MAX);
$bin->writeUInt64LE(PHP_INT_MAX);
$bin->writeInt32BE(1022);
$bin->writeInt32LE(1022);
$bin->writeFloat(3.1415926);
$bin->writeDouble(3.1415926);
$bin->write("Hello World!");
$bin->write("你好");


assert($bin->readUInt8() === 12);
assert($bin->readUInt16BE() === 24);
assert($bin->readUInt16LE() === 24);
assert($bin->readUInt32BE() === 1022);
assert($bin->readUInt32LE() === 1022);
assert($bin->readUInt64BE() === strval(PHP_INT_MAX));
assert($bin->readUInt64LE() === strval(PHP_INT_MAX));
assert($bin->readInt32BE() === 1022);
assert($bin->readInt32LE() === 1022);
assert($bin->readFloat() - 3.1415926 <= 0.0000001);
assert($bin->readDouble() - 3.1415926 <= 0.0000001);
assert($bin->read(strlen("Hello World!")) === "Hello World!");
assert($bin->read(strlen("你好")) === "你好");
// assert($bin->readableBytes() === 0);