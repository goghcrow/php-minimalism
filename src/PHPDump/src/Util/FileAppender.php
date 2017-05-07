<?php

namespace Minimalism\PHPDump\Util;


/**
 * Class FileAppender
 * @package Minimalism\PHPDump\Util
 *
 * notice: 适用于 yz-swoole >=2.x
 */
class FileAppender
{
    private $file;
    private $offset;

    public function __construct($file, $offset = null)
    {
        // yz_swoole
        // 设置AIO线程数量
        // swoole_async_set([ "thread_num" => ?,]);
        // 设置文件写入分块数
        // swoole_async_set("aio_max_buffer", 1024 * 1024);

        $this->file = $file;
        if ($offset === null) {
            if (file_exists($file)) {
                $this->offset = filesize($file);
            } else {
                $this->offset = 0;
            }
        } else {
            $this->offset = $offset;
        }
    }

    public function append($contents)
    {
        $size = strlen($contents);
        if ($size) {
            // 只有返回false才会停止写，返回false会关闭文件
            swoole_async_write($this->file, $contents, $this->offset, function($file, $size) {});
            $this->offset += $size;
        }
    }
}