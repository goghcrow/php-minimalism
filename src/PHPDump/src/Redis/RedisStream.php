<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/5/31
 * Time: 下午10:16
 */

namespace Minimalism\PHPDump\Redis;

use Minimalism\PHPDump\Buffer\Buffer;

class RedisStream
{
    private $buffer;

    public function __construct(Buffer $buffer)
    {
        $this->buffer = $buffer;
    }

    public function readLine()
    {
        $line = "";
        while ($this->buffer->readableBytes() > 0) {
            $char = $this->buffer->read(1);
            if ($char === "\r" && $this->buffer->get(1) === "\n") {
                $this->buffer->read(1);
                return $line;
            } else {
                $line .= $char;
            }
        }
        return $line;
    }
}