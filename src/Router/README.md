## php router

### pre

看了[nikic大神的文章](http://nikic.github.io/2014/02/18/Fast-request-routing-using-regular-expressions.html),讲FastRoute的实现机制，如何分块合并正则表达式,加速匹配路由;

自己也想动手写个路由,查了些资料,发现[golang的httprouter](https://github.com/julienschmidt/httprouter)比较火,使用优化过的字典树(Trie树)实现,奈何动手写一个字典树对我而言有点难度～

简化处理,只用了字典树的思路,将**path**按照**/**分割,构成树结构,进行匹配,实现了一个只支持**/:var_name/**通配符的简陋的路由～
理论上查找可以达到O(log(n)),因为有通配符存在,其实会比O(log(n))慢～

### example

    /user/:id/add
    /user/:id/delete
    /user/:id/update
	/article/:name/add
	/article/:name/delete
	/article/:name/update

**结构：**
~~~ php
[
	/ => [
		user => [
			:id => [
				add
				delete
				update
			]
		]
		article => [
			:name => [
				add
				delete
				update
			]
		]
	]
]
~~~

~~~php
<?php
namespace xiaofeng;

/**
 * Class Route
 * @method request(string $path, mixed $handler) 匹配所有httpmethod
 * @method get(string $path, mixed $handler)
 * @method post(string $path, mixed $handler)
 * @method put(string $path, mixed $handler)
 * @method patch(string $path, mixed $handler)
 * @method delete(string $path, mixed $handler)
 * @method trace(string $path, mixed $handler)
 * @method connect(string $path, mixed $handler)
 * @method options(string $path, mixed $handler)
 * @method head(string $path, mixed $handler)
 * @method dispatch($httpMethod, $uri)
 */

//$httpMethod = $_SERVER['REQUEST_METHOD'];
//$uri = $_SERVER['REQUEST_URI'];

$route = new Router;
// 只实现了最简单的占位符   /:var_name/
$route->put("/user/:id/sthother", "handler1");
$route->delete("/user/:id/sthother", "handler2");
$route->post("/user/:id/sthother", "handler3");

$ret = $route->dispatch("put", "/user/1/sthother");
var_export($ret); echo PHP_EOL;
$ret = $route->dispatch("delete", "/user/2/sthother");
var_export($ret); echo PHP_EOL;
$ret = $route->dispatch("post", "/user/3/sthother");
var_export($ret); echo PHP_EOL;
$ret = $route->dispatch("post", "/not_exist");
var_export($ret); echo PHP_EOL;
/*
array (
  0 => 'handler1',
  1 =>
  array (
    'id' => '1',
  ),
)
array (
  0 => 'handler2',
  1 =>
  array (
    'id' => '2',
  ),
)
array (
  0 => 'handler3',
  1 =>
  array (
    'id' => '3',
  ),
)
false
*/
~~~


### 测试
按照[github上的路由测试项目](https://github.com/tyler-sommer/php-router-benchmark),
添加代码
~~~php
<?php
// first-route-tests.php
// setupBenchmark
// setupXiaoRouter($benchmark, $numRoutes, $numArgs);

/**
 * Sets up XiaoRouter tests
 */
function setupXiaoRouter(Benchmark $benchmark, $routes, $args)
{
    $name = 'XiaoRouter';
    $argString = implode('/', array_map(function ($i) { return ':arg' . $i; }, range(1, $args)));
    $str = $firstStr = $lastStr = '';
    $router = new \xiaofeng\Router;
    for ($i = 0; $i < $routes; $i++) {
        list ($pre, $post) = getRandomParts();
        $str = '/' . $pre . '/' . $argString . '/' . $post;

        if (0 === $i) {
            $firstStr = str_replace(':', '', $str);
        }
        $lastStr = str_replace(':', '', $str);

        $router->get($str, 'handler' . $i);
    }

    $benchmark->register(sprintf('%s - unkown route', $name), function () use ($router, $firstStr) {
        $route = $router->dispatch('GET', '/not-real-router');
    });
    $benchmark->register(sprintf('%s - first route', $name), function () use ($router, $firstStr) {
        $route = $router->dispatch('GET', $firstStr);
    });
}
~~~

~~~php
<?php
// worst-case-tests.php
// setupBenchmark
// setupXiaoRouter($benchmark, $numRoutes, $numArgs);

/**
 * Sets up XiaoRouter tests
 */
function setupXiaoRouter(Benchmark $benchmark, $routes, $args)
{
    $name = 'XiaoRouter';

    $argString = implode('/', array_map(function ($i) { return ':arg' . $i; }, range(1, $args)));
    $str = $firstStr = $lastStr = '';
    $router = new \xiaofeng\Router;
    for ($i = 0; $i < $routes; $i++) {
        list ($pre, $post) = getRandomParts();
        $str = '/' . $pre . '/' . $argString . '/' . $post;

        if (0 === $i) {
            $firstStr = str_replace(':', '', $str);
        }
        $lastStr = str_replace(':', '', $str);

        $router->get($str, 'handler' . $i);
    }

    $benchmark->register(sprintf('%s - last route (%s routes)', $name, $routes), function () use ($router, $lastStr) {
        $route = $router->dispatch('GET', $lastStr);
    });

    $benchmark->register(sprintf('%s - unknown route (%s routes)', $name, $routes), function () use ($router) {
        $route = $router->dispatch('GET', '/not-even-real');
    });
}
~~~

**横向测试结果如下(不包括c扩展路由)：**

#### 100个路由，9个通配符，执行1000次

## Worst-case matching
This benchmark matches the last route and unknown route. It generates a randomly prefixed and suffixed route in an attempt to thwart any optimization. 100 routes each with 9 arguments.

This benchmark consists of 12 tests. Each test is executed 1,000 times, the results pruned, and then averaged. Values that fall outside of 3 standard deviations of the mean are discarded.


Test Name | Results | Time | + Interval | Change
--------- | ------- | ---- | ---------- | ------
**XiaoRouter - unknown route (100 routes)** | **988** | **0.0000804552** | **+0.0000000000** | b**aseline**
FastRoute - unknown route (100 routes) | 988 | 0.0001241159 | +0.0000436608 | 54% slower
FastRoute - last route (100 routes) | 999 | 0.0001284031 | +0.0000479479 | 60% slower
**XiaoRouter - last route (100 routes)** | **994** | **0.0002275828** | **+0.0001471276 **| **183% slower**
Symfony2 Dumped - unknown route (100 routes) | 993 | 0.0004650031 | +0.0003845480 | 478% slower
Symfony2 Dumped - last route (100 routes) | 985 | 0.0005438166 | +0.0004633614 | 576% slower
Pux PHP - unknown route (100 routes) | 975 | 0.0006638510 | +0.0005833958 | 725% slower
Pux PHP - last route (100 routes) | 991 | 0.0007342691 | +0.0006538139 | 813% slower
Symfony2 - unknown route (100 routes) | 991 | 0.0013347379 | +0.0012542828 | 1559% slower
Symfony2 - last route (100 routes) | 997 | 0.0022368687 | +0.0021564135 | 2680% slower
Aura v2 - unknown route (100 routes) | 975 | 0.0403792425 | +0.0402987874 | 50088% slower
Aura v2 - last route (100 routes) | 982 | 0.0458576946 | +0.0457772395 | 56898% slower


## First route matching
This benchmark tests how quickly each router can match the first route. 100 routes each with 9 arguments.

This benchmark consists of 7 tests. Each test is executed 1,000 times, the results pruned, and then averaged. Values that fall outside of 3 standard deviations of the mean are discarded.


Test Name | Results | Time | + Interval | Change
--------- | ------- | ---- | ---------- | ------
Pux PHP - first route | 979 | 0.0000467946 | +0.0000000000 | baseline
FastRoute - first route | 999 | 0.0000889566 | +0.0000421621 | 90% slower
Symfony2 Dumped - first route | 998 | 0.0001489290 | +0.0001021344 | 218% slower
**XiaoRouter - first route** | **998** | **0.0002507891** | **+0.0002039945** | **436% slower**
Symfony2 - first route | 993 | 0.0002525455 | +0.0002057510 | 440% slower
Aura v2 - first route | 976 | 0.0007305668 | +0.0006837722 | 1461% slower

#### 1000个路由，9个通配符，执行100次

**最差匹配情况下，快FastRoute一个数量级**
**最好匹配情况，可接受**

## Worst-case matching
This benchmark matches the last route and unknown route. It generates a randomly prefixed and suffixed route in an attempt to thwart any optimization. 1,000 routes each with 9 arguments.

This benchmark consists of 12 tests. Each test is executed 100 times, the results pruned, and then averaged. Values that fall outside of 3 standard deviations of the mean are discarded.


Test Name | Results | Time | + Interval | Change
--------- | ------- | ---- | ---------- | ------
**XiaoRouter - unknown route (1000 routes)** | **99** | **0.0000685008** | **+0.0000000000** | **baseline**
**XiaoRouter - last route (1000 routes)** | **96** | **0.0001862620** | **+0.0001177613** | **172% slower**
FastRoute - unknown route (1000 routes) | 97 | 0.0008679665 | +0.0007994658 | 1167% slower
FastRoute - last route (1000 routes) | 99 | 0.0008838153 | +0.0008153145 | 1190% slower
Symfony2 Dumped - unknown route (1000 routes) | 98 | 0.0045255812 | +0.0044570804 | 6507% slower
Symfony2 Dumped - last route (1000 routes) | 99 | 0.0050419148 | +0.0049734140 | 7260% slower
Pux PHP - last route (1000 routes) | 98 | 0.0091354409 | +0.0090669401 | 13236% slower
Pux PHP - unknown route (1000 routes) | 99 | 0.0108306071 | +0.0107621063 | 15711% slower
Symfony2 - last route (1000 routes) | 99 | 0.0163162405 | +0.0162477397 | 23719% slower
Symfony2 - unknown route (1000 routes) | 98 | 0.0163435644 | +0.0162750636 | 23759% slower
Aura v2 - last route (1000 routes) | 99 | 0.3960038387 | +0.3959353380 | 578001% slower
Aura v2 - unknown route (1000 routes) | 99 | 0.4186617268 | +0.4185932261 | 611078% slower


## First route matching
This benchmark tests how quickly each router can match the first route. 1,000 routes each with 9 arguments.

This benchmark consists of 7 tests. Each test is executed 100 times, the results pruned, and then averaged. Values that fall outside of 3 standard deviations of the mean are discarded.


Test Name | Results | Time | + Interval | Change
--------- | ------- | ---- | ---------- | ------
Pux PHP - first route | 99 | 0.0000503232 | +0.0000000000 | baseline
FastRoute - first route | 99 | 0.0000828878 | +0.0000325646 | 65% slower
Symfony2 Dumped - first route | 99 | 0.0001252854 | +0.0000749622 | 149% slower
**XiaoRouter - first route** | **97** | **0.0001970394** | **+0.0001467162** | **292% slower**
Symfony2 - first route | 97 | 0.0003279288 | +0.0002776056 | 552% slower
Aura v2 - first route | 97 | 0.0007969080 | +0.0007465848 | 1484% slower

### 结论

**O(log(n))的优势只能在路由表数量较多的情况下（最后匹配或无匹配）才能体现出来～**

**So:**

1. **正常业务下,还是用正则表达式吧,匹配自由,类型限制严格**
2. **性能敏感且路由表巨大情况下，可以考虑用树结构实现～**
3. **就像大神说的，路由一般都不是瓶颈啦～～～**