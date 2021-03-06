<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/7
 * Time: 上午1:06
 */

namespace Minimalism\A\Client;


use Minimalism\A\Client\Exception\AsyncFileException;
use Minimalism\A\Core\Async;

class AsyncFile implements Async
{
    public $complete;

    const EOF = "";
    const MAX_CHUNK = 1024 * 1024;

    public $file;
    public $type;
    public $content;
    public $offset;

    public function __construct($file)
    {
        $this->file = $file;
    }

    public function read()
    {
        $this->type = "read";
        return $this;
    }

    public function write($content)
    {
        $this->content = $content;
        $this->type = "write";
        return $this;
    }

    public function start(callable $continuation)
    {
        $this->complete = $continuation;
        if ($this->type === "read") {
            $this->getContents();
        } else if ($this->type === "write") {
            $this->putContents();
        } else {
            assert(false);
        }
    }

    private function getContents()
    {
        $this->content = "";

        /** @noinspection PhpUnusedParameterInspection */
        $r = swoole_async_read($this->file, function($filename, $content) {
            if ($content === self::EOF) {
                $cb = $this->complete;
                $cb($this->content, null);
                $this->content = "";
            } else {
                $this->content .= $content;
            }
        });

        if ($r === false) {
            $cb = $this->complete;
            $cb(null, new AsyncFileException("swoole_async_read fail"));
        }
    }

    private function putContents()
    {
        $content = substr($this->content, 0, self::MAX_CHUNK);
        if ($content === false) {
            $cb = $this->complete;
            $cb(null, new AsyncFileException("putContents fail"));
            return;
        }

        /** @noinspection PhpUnusedParameterInspection */
        $r = swoole_async_write($this->file, $content, $this->offset, function($filename, $size) {
            $this->content = substr($this->content, $size);
            $this->offset += $size;

            if ($this->content !== false && strlen($this->content)) {
                $this->putContents();
            } else {
                $cb = $this->complete;
                $cb($this->offset);
                $this->content = "";
                $this->offset = 0;
            }
        });

        if ($r === false) {
            $cb = $this->complete;
            $cb(null, new AsyncFileException("swoole_async_write fail"));
        }
    }
}