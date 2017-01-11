<?php
/**
 * User: xiaofeng
 * Date: 2016/6/14
 * Time: 22:34
 */
namespace Minimalism\Test\Router;

use Closure;
use Minimalism\Router\Router;

require_once __DIR__ . "/../../src/Router/Router.php";

//$httpMethod = $_SERVER['REQUEST_METHOD'];
//$uri = $_SERVER['REQUEST_URI'];
// 测试代码来自 https://github.com/tyler-sommer/php-router-benchmark

$numArgs = 9;
$numRouters = 10;
$count = 1000;

function bench(Closure $c) {
    $start = microtime(true);
    for ($i = 0; $i < 100000; $i++) {
        $c($i);
    }
    echo microtime(true) - $start, PHP_EOL;
}


firstCase($numArgs, $numRouters, $count);
worstCase($numArgs, $numRouters, $count);

function firstCase($numArgs, $numRouters, $count) {
    $argString = implode('/', array_map(function ($i) { return ':arg' . $i; }, range(1, $numArgs)));
    $str = $firstStr = $lastStr = '';
    $router = new Router;
    for ($i = 0; $i < $numRouters; $i++) {
        list ($pre, $post) = getRandomParts();
        $str = '/' . $pre . '/' . $argString . '/' . $post;
        if (0 === $i) {
            $firstStr = str_replace(':', '', $str);
        }
        $lastStr = str_replace(':', '', $str);
        $router->get($str, 'handler' . $i);
//        echo $str, PHP_EOL;
    }
//    echo $firstStr, PHP_EOL;

    $start = microtime(true);
    for ($n = 0; $n < $count; $n++) {
        $route = $router->dispatch('GET', '/not-even-real');
        $route = $router->dispatch('GET', $firstStr);
    }
    echo number_format((microtime(true) - $start) / $count, 10), PHP_EOL;
}

function worstCase($numArgs, $numRouters, $count) {
    $argString = implode('/', array_map(function ($i) { return ':arg' . $i; }, range(1, $numArgs)));
    $str = $firstStr = $lastStr = '';
    $router = new Router;
    for ($i = 0; $i < $numRouters; $i++) {
        list ($pre, $post) = getRandomParts();
        $str = '/' . $pre . '/' . $argString . '/' . $post;
        if (0 === $i) {
            $firstStr = str_replace(':', '', $str);
        }
        $lastStr = str_replace(':', '', $str);
        $router->get($str, 'handler' . $i);
//        echo $str, PHP_EOL;
    }
//    echo $lastStr, PHP_EOL;

    $start = microtime(true);
    for ($n = 0; $n < $count; $n++) {
        $route = $router->dispatch('GET', '/not-even-real');
        $route = $router->dispatch('GET', $lastStr);
    }
    echo number_format((microtime(true) - $start) / $count, 10), PHP_EOL;
}


function getRandomParts()
{
    $rand = md5(uniqid(mt_rand(), true));
    return [
        substr($rand, 0, 10),
        substr($rand, -10),
    ];
}