<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/7/15
 * Time: 下午3:37
 */

namespace Minimalism\Test\Buffer;


use Minimalism\Buffer\BinaryStream;
use Minimalism\Buffer\MemoryBuffer;
use Minimalism\Buffer\IteratorProtocol;
use Minimalism\Buffer\StringBuffer;
use Minimalism\Validation\P;


require __DIR__ . "/../../src/Buffer/Buffer.php";
require __DIR__ . "/../../src/Buffer/MemoryBuffer.php";
require __DIR__ . "/../../src/Buffer/StringBuffer.php";
require __DIR__ . "/../../src/Buffer/BinaryStream.php";
require __DIR__ . "/../../src/Buffer/Protocol.php";


call_user_func(function() {
    $buffer = new MemoryBuffer();
    $protocol = new IteratorProtocol($buffer);
    $protocol->parse(function(IteratorProtocol $proto) {
        $bytes = yield "\n";
        var_dump($bytes);
    });


    $protocol->write("123");
    $protocol->write("\n4");
    $protocol->write("56\n78\n");
});

echo str_repeat('-=-', 30), "\n";

call_user_func(function() {
    $buffer = new MemoryBuffer();
    $buffer->write("123");
    $buffer->write("\n4");
    $buffer->write("56\n78\n");

    $protocol = new IteratorProtocol($buffer);
    $protocol->parse(function(IteratorProtocol $proto) {
        $bytes = yield "\n";
        var_dump($bytes);
    });
});

echo str_repeat('-=-', 30), "\n";

call_user_func(function() {
    $buffer = new MemoryBuffer();
    $protocol = new IteratorProtocol($buffer);
    $protocol->parse(function(IteratorProtocol $proto) {
        $bytes = yield 1;
        var_dump($bytes);
        $bytes = yield 2;
        var_dump($bytes);
        $bytes = yield 3;
        var_dump($bytes);
    });


    $protocol->write("123");
    $protocol->write("4");
    $protocol->write("5678");
});

echo str_repeat('-=-', 30), "\n";


call_user_func(function() {
    $buffer = new MemoryBuffer();

    // --------------

    $protocol = new IteratorProtocol($buffer);
    $protocol->parse(function(IteratorProtocol $proto) {
        $a = yield $proto->readFloat();
        var_dump($a);
        $a = yield $proto->readInt32BE();
        var_dump($a);
        $a = yield $proto->readUInt8();
        var_dump($a);
    });

    // --------------

    $bs = new BinaryStream($buffer);
    $bs->writeFloat(M_PI);
    $bs->writeInt32BE(42);
    $bs->writeUInt8(100);

});

echo str_repeat('-=-', 30), "\n";



call_user_func(function() {
    $buffer = new MemoryBuffer();
    $protocol = new IteratorProtocol($buffer);
    $protocol->register("Line", ["\r\n", "\r", "\n"]);

    $protocol->parse(function(IteratorProtocol $proto) {
        $bytes = yield $proto->readLine();
        var_dump($bytes);
    });

    $protocol->write("123");
    $protocol->write("\n4");
    $protocol->write("56\r\n78\n");
});


echo str_repeat('-=-', 30), "\n";
echo str_repeat('-=-', 30), "\n";


call_user_func(function() {

    $buffer = new MemoryBuffer();
    $protocol = new IteratorProtocol($buffer);

    $protocol->parse(function(IteratorProtocol $proto) {
        $reqLine = yield "\r\n";

        $httpMsg = [];
        list($httpMsg["method"], $httpMsg["path"], $httpMsg["ver"]) = explode(" ", $reqLine);

        $hdrs = [];
        while (true) {
            $hdrLine = yield "\r\n";
            if (empty($hdrLine)) {
                break;
            }

            list($hdrKey, $hdrVal) = explode(":", $hdrLine);
            $hdrs[strtolower($hdrKey)] = trim($hdrVal);
        }

        $httpMsg["headers"] = $hdrs;

        if (isset($httpMsg["headers"]["content-length"])) {
            $bodyLen = intval($httpMsg["headers"]["content-length"]);
            $httpMsg["body"] = yield $bodyLen;
        }

        var_dump($httpMsg);
    });

    // ------------------------------------------------------------------------------------------------
    $serv = new \swoole_server("0.0.0.0", 8888);
    $serv->set(["worker_num" => 1]);
    $serv->on("receive", function(\swoole_server $serv, $_, $fd, $recv) use($buffer) {
        $buffer->write($recv);
    });
    $serv->start();
});


echo str_repeat('-=-', 30), "\n";
