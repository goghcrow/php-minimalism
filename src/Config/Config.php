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
     * 按照 recursiveMerge 策略 覆盖
     */
    const SHARE_DIR = "share";

    /**
     * 单个配置文件公共配置项,其他配置项目均继承自此项
     * 按照 recursiveMerge 策略 覆盖
     */
    const DEFAULT_CONF_ITEM = "share";

    protected static $conf;

    /**
     * get(a.b.c, default)
     * @param string|null $keypath
     * @param mixed $default
     * @return mixed
     */
    public static function get($keypath = null, $default = null)
    {
        if ($keypath === null) {
            return static::$conf;
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

        $value = self::composeKeypath($paths, $value);
        self::$conf = static::recursiveMerge(self::$conf, $value);
        return true;
    }

    /**
     * 载入所有约定的配置
     * @param string $dir 不同环境的配置文件的路径
     * @param string $env 环境名称,不同环境的配置存放在以环境命名的文件夹下
     * @param string $ext 配置文件扩展名
     */
    public static function load($dir, $env = "", $ext = "php")
    {
        // 载入跨环境公共配置
        $defaultConf = static::loadDir($dir, self::SHARE_DIR, $ext);
        $envConf = static::loadDir($dir, $env, $ext);
        self::$conf = static::recursiveMerge($defaultConf, $envConf);
    }


    /**
     * 载入整个文件夹的配置
     * @param string $dir
     * @param string $env
     * @param string $ext
     * @return array
     */
    protected static function loadDir($dir, $env, $ext)
    {
        if (!is_dir("$dir/$env")) {
            throw new InvalidArgumentException("Invalid path $dir/$env");
        }

        $dir = realpath("$dir/$env");
        $regex = '/^.+\.' . $ext . '$/i';
        $confIter = static::scan($dir, $regex, static::loadFile($dir));
        return call_user_func_array([static::class, "recursiveMerge"], iterator_to_array($confIter));
    }

    /**
     * 加载一个文件的配置
     * @param string $dir 需要移除的路径部分
     * @return \Closure
     */
    protected static function loadFile($dir)
    {
        return function($file) use($dir) {
            $dirLen = mb_strlen($dir);
            /** @noinspection PhpIncludeInspection */
            $singleFileConf = require/*_once*/ $file; // Notice Security Problem
            if (!is_array($singleFileConf)) {
                throw new \RuntimeException("conf file $file should return array");
            }

            $singleFileConf = static::composeDefault($singleFileConf);
            // path/(a/b/c/xxx).php 提起a/b/c/xxx作为keypath
            $ext = mb_strrchr($file, ".");
            $keypath = mb_substr($file, $dirLen + 1, -mb_strlen($ext));
            return static::composeKeypath(explode("/", $keypath), $singleFileConf);
        };
    }

    /**
     * 递归扫描文件
     * @param string $dir
     * @param string $regex
     * @param callable $callback
     * @return \Generator
     */
    protected static function scan($dir, $regex, callable $callback = null)
    {
        $dirIter = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $iterIter = new RecursiveIteratorIterator($dirIter, RecursiveIteratorIterator::LEAVES_ONLY);
        $regexIter = new RegexIterator($iterIter, $regex, RegexIterator::GET_MATCH);

        foreach($regexIter as $file => $_) {
            yield $callback === null ? $file : $callback($file);
        }
    }

    /**
     * 根据相对路径展开配置数组
     * (a/b/c confArr) --> [a => [b => [c => confArr]]]
     * @param array $pathes
     * @param $value
     * @return array
     */
    protected static function composeKeypath(array $pathes, $value)
    {
        $cur = count($pathes);
        while (--$cur >= 0) {
            $value = [$pathes[$cur] => $value];
        }
        return $value;
    }

    /**
     * 如果存在默认参数,则提取默认参数,补全其他参数
     * [ [default => [a=>1, b=>2]], [b=>3] ]  -->  [a=>1, b=>3]
     * @param array $config
     * @return array
     */
    protected static function composeDefault(array $config)
    {
        if (!isset($config[static::DEFAULT_CONF_ITEM])) {
            return $config;
        }

        $default = $config[static::DEFAULT_CONF_ITEM];
        unset($config[static::DEFAULT_CONF_ITEM]);

        foreach ($config as $k => &$v) {
            $v = static::recursiveMerge($default, $v);
        }
        unset($v);
        return $config;
    }

    /**
     * 递归合并多维数组
     * @params ...$args array1, array2, ...arrayn
     * @return array
     */
    protected static function recursiveMerge(/*...$args*/)
    {
        $args = func_get_args();
        if (empty($args)) {
            return [];
        }

        $ret = [];
        foreach ($args as $arg) {
            if (!is_array($arg) || empty($arg)) {
                continue;
            }
            foreach ($arg as $k => $v) {
                if (!isset($ret[$k])) {
                    $ret[$k] = $v;
                    continue;
                }

                if (is_array($v) && is_array($ret[$k])) {
                    $ret[$k] = static::recursiveMerge($ret[$k], $v);
                } else {
                    $ret[$k] = $v;
                }
            }
        }
        return $ret;
    }
}