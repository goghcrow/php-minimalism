<?php

namespace Minimalism\FakeServer\Buffer;


interface Buffer
{
    public function readableBytes();

    public function get($len);

    public function read($len);

    public function write($bytes);
}