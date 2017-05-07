<?php

namespace Minimalism\PHPDump\Buffer;


class BufferFactory
{
    public static function make($type = "memory", $size = 8192)
    {
        switch ($type) {
            case "memory":
                return new MemoryBuffer($size);
            case "string":
            default:
                return new StringBuffer();
        }
    }
}