<?php

namespace Minimalism\functions;

/**
 * scan dirs
 * @param string|array $dirs path or array of path
 * @param string $regex
 * @param callable $filter
 * @param bool $realPath yield path or \SplFileInfo
 * @return \Generator
 * @author xiaofeng
 * @Usage
 *  1. scan("\tmp", '/.*\.php/')
 *  2. scan(["\tmp", "\Users"], '/.*\.php/')
 *  3. scan("\tmp", null, function(SplFileInfo $current, $path, $iter) { return true or false })
 *  4. scan(["\tmp", "\Users"], null, function(SplFileInfo $current, $path, $iter) { return true or false })
 */
function scan($dirs, $regex = null, callable $filter = null, $realPath = true)
{
    $dirs = (array)$dirs;

    $appendIter = new \AppendIterator();
    foreach ($dirs as $dir) {
        $iter = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
        if ($filter) {
            $iter = new \RecursiveCallbackFilterIterator($iter, $filter);
        }
        $iter = new \RecursiveIteratorIterator($iter, \RecursiveIteratorIterator::LEAVES_ONLY);

        if ($regex) {
            $iter = new \RegexIterator($iter, $regex, \RegexIterator::GET_MATCH);
        }
        $appendIter->append($iter);
    }

    foreach ($appendIter as $file) {
        if ($regex) {
            yield $realPath ? realpath($file[0]) : new \SplFileInfo($file[0]);
        } else {
            yield $realPath ? $file->getRealPath() : $file;
        }
    }
}