<?php

namespace Minimalism\FakeServer\Buffer;


class BufferFactory
{
    /**
     * @param string $type
     * @param int $size
     * @return Buffer
     */
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