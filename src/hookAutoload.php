<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/6/29
 * Time: 上午1:24
 */

require __DIR__ . "/../vendor/autoload.php";

// 必须在引入autoload之后执行
hookAutoload();


// 模拟 c# cctor java 静态初始化代码
// 替换autoload函数
function hookAutoload()
{
    $funcs = spl_autoload_functions();

    $staticInitFuncs = [];
    foreach ($funcs as $func) {
        spl_autoload_unregister($func);
        $staticInitFuncs[] = function($class) use($func) {
            echo "[autoload] $class\n"; // debug
            $func($class);

            // 类或接口完成加载后, 自动加载队列停止执行autoload函数, 保证__static 执行一次
            if (class_exists($class) && method_exists($class, "__static")) {
                call_user_func("$class::__static");
            }
        };
    }
    foreach ($staticInitFuncs as $func) {
        spl_autoload_register($func);
    }
}
