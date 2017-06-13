<?php

namespace Minimalism\FakeServer\Buffer;


interface Buffer
{
    public function get($len);

    public function readableBytes();

    public function writableBytes();

    public function prependableBytes();

    public function read($len);

    public function readFull();

    public function write($bytes);

    public function prepend($bytes);

    public function peek($offset, $len = 1);

    public function search($str, $offset = 0);

    public function getUntil($sep, $included = false);

    public function readUntil($sep, $included = false);

    public function getLine($br = "\r\n", $included = false);

    public function readLine($br = "\r\n", $included = false);

    public function reset();
}