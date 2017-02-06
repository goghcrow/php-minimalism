<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/4
 * Time: 上午11:17
 */

namespace Minimalism\Config;


/**
 * Class Converter
 *
 * 数组配置与ini配置转换
 */
final class Converter
{
    /**
     * @var string 基础目录，会在生成的配置key中排除
     */
    public $basedir;

    public $map;

    public function __construct($basedir)
    {
        $this->basedir = realpath($basedir);
        $this->map = [];
    }

    public function __toString()
    {
        $r = [];
        foreach ($this->map as $file => $kv) {
            $file = substr($file, strlen($this->basedir) + 1);
            $r[] = "; $file";
            foreach ($kv as $k => $v) {
                if (is_string($v)) {
                    $r[] = "$k=\"$v\"";
                } else {
                    $r[] = "$k=$v";
                }
            }
            $r[] = PHP_EOL;
        }
        return implode(PHP_EOL, $r);
    }

    public function scanDir($dir)
    {
        $dir = realpath($dir);
        $files = $this->scan($dir, '/.*\.php/');
        foreach ($files as $file) {
            $this->scanFile($file);
        }
        return $this;
    }

    public function scanFile($file)
    {
        ob_start();
        /** @noinspection PhpIncludeInspection */
        $conf = require $file;
        // TODO DEFAULT_CONF_ITEM

        if (!preg_match('#\s#', $b = ob_get_clean())) {
            echo $b;
        }

        if (empty($conf)) {
            return [];
        }
        $path = substr($file, strlen($this->basedir) + 1, -strlen("php"));

        $prefix = str_replace("/", ".", $path);
        $this->map[$file] = $this->reduceDim($conf, $prefix);
        return $this;
    }

    private function reduceDim(array $conf, $prefix = null)
    {
        $r = [];
        foreach ($conf as $path => $val) {
            if (is_array($val)) {
                $subR = $this->expandVal($path, $val, $prefix);
                $r = array_merge($r, $subR);
            } else {
                $r["{$prefix}{$path}"] = $val; // baseline
            }
        }
        return $r;
    }

    private function expandVal($path, array $val, $prefix = null)
    {
        $r = [];
        $oneDimArr = $this->reduceDim($val);
        foreach ($oneDimArr as $subpath => $finalVal) {
            $r["{$prefix}{$path}.{$subpath}"] = $finalVal;
        }
        return $r;
    }

    private function scan($dir, $regex)
    {
        $iter = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $iter = new \RecursiveIteratorIterator($iter, \RecursiveIteratorIterator::LEAVES_ONLY);
        $iter = new \RegexIterator($iter, $regex, \RegexIterator::GET_MATCH);

        foreach ($iter as $file) {
            yield realpath($file[0]);
        }
    }
}