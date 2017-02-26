<?php

namespace Minimalism\Config;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use InvalidArgumentException;

class Config
{
    /**
     * 公共配置文件夹名称,其他配置均继承自此文件夹配置
     * 按照 merge 策略 覆盖
     */
    const SHARE_DIR = "share";

    /**
     * 单个配置文件公共配置项,其他配置项目均继承自此项
     * 按照 merge 策略 覆盖
     */
    const DEFAULT_CONF_ITEM = "common";

    public static $conf;

    /**
     * get(a.b.c, default)
     * @param string|null $keypath
     * @param mixed $default
     * @return mixed
     */
    public static function get($keypath = null, $default = null)
    {
        if ($keypath === null) {
            return self::$conf;
        }

        $pathes = explode(".", $keypath);
        if (empty($pathes)) {
            return $default;
        }

        $val = self::$conf;
        foreach ($pathes as $path) {
            if (!isset($val[$path])) {
                return $default;
            }
            $val = $val[$path];
        }
        return $val;
    }

    /**
     * set("a.b.c", val)
     * @param string $keypath
     * @param mixed $value
     * @return bool
     */
    public static function set($keypath, $value)
    {
        $paths = explode(".", $keypath);
        if (empty($paths)) {
            return false;
        }

        $value = self::expand($paths, $value);
        self::$conf = self::merge(self::$conf, $value);
        return true;
    }

    /**
     * 载入所有约定的配置
     * @param string $dir 不同环境的配置文件的路径
     * @param string $env 环境名称,不同环境的配置存放在以环境命名的文件夹下
     * @param string $ext 配置文件扩展名
     * @return array
     */
    public static function load($dir, $env, $ext = "php")
    {
        // 载入跨环境公共配置
        $defaultConf = self::loadDir($dir, self::SHARE_DIR, $ext);
        $envConf = self::loadDir($dir, $env, $ext);

        self::$conf = self::merge(...array_values($defaultConf), ...array_values($envConf));
    }

    /**
     * 转换为ini配置
     */
    public static function toIni()
    {
        $kv = self::array2kv(self::get());

        $r = [];
        foreach ($kv as $k => $v) {
            if (is_string($v)) {
                $r[] = "$k=\"$v\"";
            } else {
                $r[] = "$k=$v";
            }
        }

        return implode(PHP_EOL, $r);
    }

    /**
     * 载入整个文件夹的配置
     * @param string $basedir
     * @param string $env
     * @param string $ext
     * @return array
     */
    public static function loadDir($basedir, $env, $ext = "php")
    {
        assert(is_dir("$basedir/$env"));

        $basedir = realpath("$basedir/$env");
        $regex = '#^.+\.' . $ext . '$#i';
        $iter = self::scan($basedir, $regex);

        $r = [];
        foreach ($iter as $file) {
            // path/(a/b/c/xxx).php --> a/b/c/xxx作为keypath
            $path = substr($file, strlen($basedir) + 1, -strlen(strrchr($file, ".")));
            $paths = explode("/", $path);

            $conf = require $file;
            assert(is_array($conf));
            $r[$file] = self::expand($paths, self::inherit($conf));
        }

        return $r;
    }

    /**
     * 根据相对路径展开配置数组
     * (a/b/c confArr) --> [a => [b => [c => confArr]]]
     * @param array $pathes
     * @param $value
     * @return array
     */
    public static function expand(array $pathes, $value)
    {
        $cur = count($pathes);
        while (--$cur >= 0) {
            $value = [$pathes[$cur] => $value];
        }
        return $value;
    }

    /**
     * 继承默认参数
     * [ [default => [a=>1, b=>2]], [b=>3] ]  -->  [a=>1, b=>3]
     * @param array $config
     * @return array
     */
    public static function inherit(array $config)
    {
        if (!isset($config[self::DEFAULT_CONF_ITEM])) {
            return $config;
        }

        $default = $config[self::DEFAULT_CONF_ITEM];
        unset($config[self::DEFAULT_CONF_ITEM]);

        foreach ($config as $k => &$v) {
            $v = self::merge($default, $v);
        }
        unset($v);
        return $config;
    }

    /**
     * 多维数组 递归合并
     * @param array $args
     * @return array
     */
    public static function merge(...$args)
    {
        if (empty($args)) {
            return [];
        }

        $ret = [];
        foreach ($args as $arg) {
            if (!is_array($arg) || empty($arg)) {
                continue;
            }
            foreach ($arg as $k => $v) {
                if (isset($ret[$k])) {
                    if (is_array($v) && is_array($ret[$k])) {
                        $ret[$k] = self::merge($ret[$k], $v);
                    } else {
                        $ret[$k] = $v;
                    }
                } else {
                    $ret[$k] = $v;
                }
            }
        }
        return $ret;
    }

    /**
     * 数据降维 [a => [b => [c => d]]] --> [a.b.c => d]
     * @param array $conf
     * @param null $prefix
     * @return array
     */
    public static function array2kv(array $conf, $prefix = null)
    {
        $r = [];
        foreach ($conf as $path => $val) {
            if (is_array($val)) {
                $subR = self::expandVal($path, $val, $prefix);
                $r = array_merge($r, $subR);
            } else {
                $r["{$prefix}{$path}"] = $val; // baseline
            }
        }
        return $r;
    }

    private static function expandVal($path, array $val, $prefix = null)
    {
        $r = [];
        $oneDimArr = self::array2kv($val);
        foreach ($oneDimArr as $subpath => $finalVal) {
            $r["{$prefix}{$path}.{$subpath}"] = $finalVal;
        }
        return $r;
    }

    public static function scan($dir, $regex)
    {
        $iter = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $iter = new \RecursiveIteratorIterator($iter, \RecursiveIteratorIterator::LEAVES_ONLY);
        $iter = new \RegexIterator($iter, $regex, \RegexIterator::GET_MATCH);

        foreach ($iter as $file) {
            yield realpath($file[0]);
        }
    }
}