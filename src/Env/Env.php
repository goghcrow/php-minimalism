<?php

namespace Minimalism\Env;


/**
 * 1. $_ENV 依赖 php.ini 依赖variables_order变量解析顺序
 * 默认为 EGPCS (Environment, Get, Post, Cookie, and Server), 需要有E,开启变量$_ENV解析
 * 2. getenv() 大小写不敏感
 *
 * 除php.ini文件与getopt外,其他均大小写不敏感
 * 读取优先顺序:
 * cli: getopt > env > php.ini
 * web: GET > POST > COOKIE > HEADER > env > php.ini
 */
final class Env
{
    public static function get($key, $else = null)
    {
        if (self::isCLI()) {
            return self::cli($key, $else);
        } else {
            return self::web($key, $else);
        }
    }

    private static function isCLI()
    {
        return defined("STDIN");
        // or return isset($_SERVER["argv"]);
        // or return strncasecmp(php_sapi_name(), "cli", 3) === 0;
    }

    private static function cli($key, $else = null)
    {
        $opts = getopt("$key::", ["$key::"]);
        if (isset($opts[$key])) {
            return $opts[$key];
        }

        $upperKey = strtoupper($key);
        $env = array_change_key_case($_ENV, CASE_UPPER); // $_ENV;
        if (isset($env[$upperKey])) {
            return $env[$upperKey];
        }

        $value = get_cfg_var($key) ?: getenv($key);
        if ($value) {
            return $value;
        }

        return $else;
    }

    private static function web($key, $else = null)
    {
        $get = array_change_key_case($_GET, CASE_UPPER);
        if (isset($get[$key])) {
            return $get[$key];
        }

        $post = array_change_key_case($_POST, CASE_UPPER);
        if (isset($post[$key])) {
            return $post[$key];
        }

        $cookie = array_change_key_case($_COOKIE, CASE_UPPER);
        if (isset($cookie[$key])) {
            return $cookie[$key];
        }

        $key = strtoupper($key);
        $server = array_change_key_case($_SERVER, CASE_UPPER);
        if (isset($server["HTTP_$key"])) {
            return $server["HTTP_$key"];
        }

        $value = get_cfg_var($key) ?: getenv($key);
        if ($value) {
            return $value;
        }

        return $else;
    }
}