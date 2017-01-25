#!/usr/bin/env php
<?php
/**
 * ssh-run
 * @author chuxiaofeng
 *
 * ./ssh-run.php pf-api "df -h"
 * ./ssh-run.php scrm-api "grep '\\\\[2017\\\\-01\\\\-04 0' /data/logs/supervisord/scrm-api-1-stdout.log"
 * ./ssh-run.php scrm-api "tail -10000 /data/logs/supervisord/scrm-api-1-stdout.log | grep -A 5 -B 5 exception"
 *
 * 注意: ssh 执行需要转义两次
 */

if (!isset($argv[1]) || !isset($argv[2]) || $argv[1] === "-h" || $argv[1] === "--help") {
    Terminal::put("Usage: $argv[0] appname cmd\nExample:\n", Terminal::BRIGHT);
    Terminal::put(<<<'RAW'
./ssh-run.php scrm-api "grep '\\\\[2017\\\\-01\\\\-04 0' /data/logs/supervisord/scrm-api-1-stdout.log | tail -2"
RAW
        . "\n", Terminal::FG_GREEN);
    exit(1);
}

$appName = $argv[1];
$cmd = $argv[2];

foreach (getHostList($appName) as $host) {
    Terminal::put("=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-", Terminal::BRIGHT);
    echo "\n";
    Terminal::put($host, Terminal::FG_GREEN, Terminal::BRIGHT);
    echo "\n";
    echo `ssh -A $host $cmd`;
    echo "\n";
}



//=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
//
//=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
function request($url, $method, array $header, $body, $timeout = 60)
{
    $header = $header + [
        "Connection" => "close",
        "Content-Length" => strlen($body),
    ];

    $header = array_reduce(array_keys($header), function($curry, $k) use($header) {
        return $curry . "$k: $header[$k]\r\n";
    }, "");

    $opts = [
        "http" => [
            "method"  => $method,
            "header"  => $header,
            "content" => $body,
            "timeout" => $timeout
        ],
        "ssl" => [
            "verify_peer" => false,
            "verify_peer_name" => false,
        ]
    ];
    $ctx  = stream_context_create($opts);

    // echo  "$method $url\n";
    // 注意顺序, file_get_contents 之后才可以使用$http_response_header
    return [
        "body" => file_get_contents($url, false, $ctx),
        "header" => $http_response_header,
    ];
}
function get($url, array $header, array $query, $timeout = 60)
{
    $query = http_build_query($query);
    return request("$url?$query", "GET", $header, "", $timeout);
}
function post($url, array $header, $body, $timeout = 60)
{
    return request($url, "POST", $header, $body, $timeout);
}

//=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
//
//=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
function queryCMDB($api, array $query)
{
    $url = "https://eva.qima-inc.com/api/cmdb/$api/";
    // 默认模糊检索名称
    $query = $query + [
        "per_page" => 100000,
        "page"=>1,
        "field"=>"name",
        "keyword"=>"",
        "sort_field"=>"id",
        "sort_order"=>"descend",
        "fuzzy" => 1, // 打开模糊检索, 精确匹配
    ];

    $res = get($url, [
        "Content-type"=> "application/x-www-form-urlencoded",
        "Accept"=> "application/json",
        "authorizationtype" => "token",
        "authorizationcode" => "4e48203b582e4653",
    ], $query);

    return json_decode($res["body"], true)["data"]["value"];
}
function queryHostGroups(array $query)
{
    return queryCMDB("hostgroups", $query);
}
function queryPeoples(array $query)
{
    return queryCMDB("peoples", $query);
}
function queryHosts(array $query)
{
    return queryCMDB("hosts", $query);
}

function getHostList($app)
{
    $hostGroups = queryHostGroups([
        "filed" => "name",
        "keyword" => "{$app}host",
        "fuzzy" => 0,
    ]);

    if (empty($hostGroups)) {
        $hostGroups = queryHostGroups([
            "filed" => "hostgroup",
            "keyword" => "{$app}host", // online
            "fuzzy" => 0, // 关闭模糊
        ]);
    }

    if (empty($hostGroups)) {
        return [];
    }

    $hostGroupId = $hostGroups[0]["id"];
    $hosts = queryHosts([
        "hostgroup_id" => $hostGroupId,
    ]);
    return array_column($hosts, "name");
}


//=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
//
//=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
/**
 *       ╔╦╗┌─┐┬─┐┌┬┐┬┌┐┌┌─┐┬
 * Class  ║ ├┤ ├┬┘│││││││├─┤│
 *        ╩ └─┘┴└─┴ ┴┴┘└┘┴ ┴┴─┘
 * @author xiaofeng
 *
 * ANSI/VT100 Terminal Control Escape Sequences
 * @standard http://www.termsys.demon.co.uk/vtansi.htm
 *
 */
class Terminal
{
    const ESC          = "\033";

    const BRIGHT       = 1;
    const DIM          = 2;
    const UNDERSCORE   = 4;
    const BLINK        = 5;
    const REVERSE      = 7;
    const HIDDEN       = 8;

    const FG_BLACK     = 30;
    const FG_RED       = 31;
    const FG_GREEN     = 32;
    const FG_YELLOW    = 33;
    const FG_BLUE      = 34;
    const FG_MAGENTA   = 35;
    const FG_CYAN      = 36;
    const FG_WHITE     = 37;

    const BG_BLACK     = 40;
    const BG_RED       = 41;
    const BG_GREEN     = 42;
    const BG_YELLOW    = 43;
    const BG_BLUE      = 44;
    const BG_MAGENTA   = 45;
    const BG_CYAN      = 46;
    const BG_WHITE     = 47;

    /**
     * Display
     * @param string $text
     * @param array $attrs
     *
     * Set Attribute Mode Format:
     *  <ESC>[{attr1};...;{attrn}m
     * e.g. \033[4;34;mhello 蓝色下划线文件hello
     * \033[0m
     */
    public static function put($text, ...$attrs) {
        // $text = addslashes($text);
        $resetAll = static::ESC . "[0m";
        $attrStr = implode(";", array_map("intval", $attrs));
        $buffer = static::ESC . "[{$attrStr}m{$text}" . $resetAll;
        echo $buffer;
    }
}