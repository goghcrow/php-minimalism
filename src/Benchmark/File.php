<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/17
 * Time: 上午12:16
 */

namespace Minimalism\Benchmark;


class File
{
    private $file;
    private $content;
    private $offset;
    private $chunk;
    private $complete;

    public function __construct($file, $chunk = 1024 * 1024)
    {
        $this->file = $file;
        $this->chunk = $chunk;
    }

    public function write($content, $offset, $complete)
    {
        $this->content = $content;
        $this->offset = $offset;
        $this->putContents();
        $this->complete = $complete;
    }

    private function putContents()
    {
        $content = substr($this->content, 0, $this->chunk);
        if ($content === false) {
            call_user_func($this->complete, false);
            return false;
        }

        return swoole_async_write($this->file, $content, $this->offset, function($filename, $size) {
            $this->content = substr($this->content, $size);
            $this->offset += $size;

            if ($this->content !== false && strlen($this->content)) {
                $this->putContents();
            } else {
                call_user_func($this->complete, $this->offset);
                $this->content = "";
                $this->offset = 0;
            }
        });
    }
}