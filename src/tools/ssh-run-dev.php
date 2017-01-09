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

    const RESET_ALL    = 0;

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

    const ERA_UP            = "1J";
    const ERA_DOWN          = "J";
    const ERA_LEFT          = "1K";
    const ERA_RIGHT         = "K";
    const ERA_LINE          = "2K";
    const ERA_SCREEN        = "2J";

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

    /**
     * 手动控制输出格式
     * @param array $attrs
     */
    public static function attr(array $attrs) {
        $attrStr = implode(";", array_map("intval", $attrs));
        echo static::ESC . "[{$attrStr}m";
    }

    /**
     * @param $text
     */
    public static function replace($text)
    {
        $numNewLines = substr_count($text, "\n");
        // 光标移动到第0列
        echo chr(27) . "[0G";
        echo $text;
        // 光标向上移动
        echo chr(27) . "[" . $numNewLines ."A";
    }

    /**
     * 清除
     * @param $attr
     * @param bool $return
     * @return string
     */
    public static function erase($attr, $return = false) {
        $buffer = static::ESC . "[$attr";
        if ($return) {
            return $buffer;
        }
        echo $buffer;
        return "";
    }
}

//=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
//=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
//=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-

//print_r(getHostList("ic"));
//print_r(getHostList("scrm-api"));
//print_r(getHostList("scrm-web"));
//print_r(getHostList("showcase-api"));
//print_r(getHostList("material-api"));
//print_r(getHostList("pf-api"));
//print_r(getHostList("pf-web"));

/*
$hostGroups = queryHostGroups([
    "filed" => "name",
    "keyword" => "pf",
    "fuzzy" => 1, // 模糊匹配所有环境
]);
print_r($hostGroups);
//*/



//$hostList = implode(",", getHostList($appName));
//echo `salt -L "$hostList" cmd.run "$cmd"`;


// ./ssh-run.php scrm-api "tail -2 /data/logs/supervisord/scrm-api-1-stdout.log"
// ./ssh-run.php scrm-api "grep '\\\\[2017\\\\-01\\\\-04 0' /data/logs/supervisord/scrm-api-1-stdout.log | tail -2"



//=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
// 这三个地址检索条件没有用 !!!
// $hosts = "https://ava.qima-inc.com/api/eva/cmdb/hosts/?per_page=100000&page=1&field=name&keyword=&sort_field=id&sort_order=descend";
// $hostgroups = "https://ava.qima-inc.com/api/eva/cmdb/hostgroups/?per_page=100000&page=1&field=name&keyword=&sort_field=id&sort_order=descend";
// $peoples = "https://ava.qima-inc.com/api/eva/cmdb/peoples/?per_page=100000&page=1&field=name&keyword=&sort_field=id&sort_order=descend";


// 这三个地址可以检索, 但是 需要在header中添加token
// "authorizationtype" => "token",
// "authorizationcode" => "4e48203b582e4653",
// $hosts = "https://eva.qima-inc.com/api/cmdb/hosts/?per_page=100000&page=1&field=name&keyword=&sort_field=id&sort_order=descend";
// $hostgroups = "https://eva.qima-inc.com/api/cmdb/hostgroups/?per_page=100000&page=1&field=name&keyword=&sort_field=id&sort_order=descend";
// $peoples = "https://eva.qima-inc.com/api/cmdb/peoples/?per_page=100000&page=1&field=name&keyword=&sort_field=id&sort_order=descend";

//=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
/*
MANIFESTS = {
        "host": {
            "fields": {
                "id": "",
                "name": "",
                "dnsip": "",
                "allip": "",
                "oobip": "",
                "sn": "",
                "parent_id": "",
                "parent": {"parent_id": "host.name"},
                "type": ((0, "physical"), (1, "virtual")),
                "os": ((0, "centos5"), (1, "centos6"), (2, "centos7"), (3, "Ubuntu 14.04 64bit"),
                    (4,"Windows 2008 64bit"), (5, "Mac OS X")),
                "model": ((0, "DEFAULT"), (1, "HDP"), (2, "SSD"), (3, "STD"), (4, "LVS")),
                "idc_env": ((0, "prod"), (1, "pre"), (2, "qatb"), (3, "oa"), (4, "qat"), (5, "dev")),
                "status": ((0, "ready"), (1, "working_online"), (2, "working_offline"),
                    (3, "broken"), (4, "buffer"), (5, "maintenance")),
                "cpu_num": "",
                "mem_size": "",
                "hostgroup_id": "",
                "hostgroup": {"hostgroup_id": "hostgroup.name"},
                "idc_id": "",
                "idc": {"idc_id": "idc.name"},
                "rack": "",
                "slot": "",
                "buy_time": "",
                "cost": "",
                "is_virtual": "",
                "is_delete": "",
                "comment": "",
                "created": "",
                "motified": "",
            },
            "query": {
                "status": "status[]",
                "type": "type[]",
                "model": "model[]",
                "idc_env": "idc_env",
                "application_name": "application_name",
                "people_username": "people_username",
                "is_virtual": "is_virtual",
            },
        },
        "hostgroup": {
            "fields": {
                "id": "",
                "is_delete": "",
                "created": "",
                "motified": "",
                "name": "",
                "desc": "",
                "application_id": "",
                "application": {"application_id": "application.name"},
            },
        },
        "idc": {
            "fields": {
                "id": "",
                "is_delete": "",
                "created": "",
                "motified": "",
                "name": "",
                "desc": "",
            },
        },
        "product": {
            "fields": {
                "id": "",
                "is_delete": "",
                "created": "",
                "motified": "",
                "name": "",
                "desc": "",
                "department_id": "",
                "department": {"department_id": "department.name"},
            },
        },
        "application": {
            "fields": {
                "id": "",
                "is_delete": "",
                "created": "",
                "motified": "",
                "name": "",
                "desc": "",
                "product_id": "",
                "product": {"product_id": "product.name"},
                "dba_ids": "",
                "dev_ids": "",
                "pe_ids": "",
                "pm_ids": "",
                "qa_ids": "",
                "scm_ids": "",
                "sec_ids": "",
            },
        },
        "department": {
            "fields": {
                "id": "",
                "is_delete": "",
                "created": "",
                "motified": "",
                "name": "",
                "desc": "",
                "leader_id": "",
                "leader": {"leader_id": "people.username"},
            },
        },
        "people": {
            "fields": {
                "id": "",
                "is_delete": "",
                "created": "",
                "motified": "",
                "username": "",
                "realname": "",
                "key": "",
            },
        },
}
*/