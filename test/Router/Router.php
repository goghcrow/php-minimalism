<?php
/**
 * User: xiaofeng
 * Date: 2016/6/14
 * Time: 22:34
 */
namespace Minimalism\Test\Router;

use Minimalism\Router\Router;

require_once __DIR__ . "/../../src/Router/Router.php";

//$httpMethod = $_SERVER['REQUEST_METHOD'];
//$uri = $_SERVER['REQUEST_URI'];


$route = new Router;
$route->get("/a/b/c1", "1");
$route->get("/a/b/c2", "2");
$route->get("/a/b/c3", "3");
$route->get("/a/b/c4", "4");
$ret = $route->dispatch("get", "/a/b/c4");
assert($ret[0] === "4");

$route = new Router;
$route->get("/a/:b/c1", "1");
$route->get("/a/b/c2", "2");
$route->get("/a/b/c3", "3");
$route->get("/a/:b/c4", "4");
$ret = $route->dispatch("get", "/a/b/c4");
assert($ret[0] === "4");
assert($ret[1]["b"] === "b");


$route = new Router();
$route->get("/", 111);
$ret = $route->dispatch("get", "/");
assert($ret[0] === 111);


$route = new Router();
$route->get("/a/:b/c", "A");
$route->post("/a/:a/y/:y/z1", "B");
$route->post("/a/:a/y/:y/z2", "C");
$route->put("/q/w/e", "C");
$route->get("/", "root");
$ret = $route->dispatch("post", "/a/1/y/nihao/z2");
assert($ret[0] === "C");
assert($ret[1]["a"] === "1");
assert($ret[1]["y"] === "nihao");



$route = new Router();
$route->request("/a/:b/c", "REQUEST");
$route->get("/a/:b/c", "GET");
// 匹配到request
$ret = $route->dispatch("post", "/a/1/c/");
assert($ret[0] === "REQUEST");
// 精确匹配到get
$ret = $route->dispatch("get", "/a/1/c/");
assert($ret[0] === "GET");


$route = new Router();
$route->get("/:first", "handler1");
$route->get("/:first/others", "handler2");
$ret = $route->dispatch("get", "/gaga");
assert($ret[0] === "handler1");
assert($ret[1]["first"] === "gaga");
$ret = $route->dispatch("get", "/gaga/others");
assert($ret[0] === "handler2");
assert($ret[1]["first"] === "gaga");


$route = new Router();
$route->get("/:first1", "handler");
$route->get("/:first2/others", "handler");
$ret = $route->dispatch("get", "/gaga");
