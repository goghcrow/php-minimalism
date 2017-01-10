### 关于php变量解析顺序 variables_order

[ref](http://stackoverflow.com/questions/1312871/what-does-egpcs-mean-in-php)

> The manual about the directive might help you a bit more : variables_order (quoting) :

> Sets the order of the EGPCS (Environment, Get, Post, Cookie, and Server) variable parsing.
> For example, if variables_order is set to "SP" then PHP will create the superglobals  $_SERVER and $_POST,
> but not create  $_ENV, $_GET, and $_COOKIE.
> Setting to "" means no superglobals will be set.

> Also note (quoting again) :

> The content and order of $_REQUEST is also affected by this directive.
> I suppose this option was more important a while ago,
> when register_globals was still something used,
> as the same page states (quoting) :

> If the deprecated register_globals directive is on (removed as of PHP 6.0.0),
> then variables_order also configures the order the ENV, GET, POST, COOKIE and SERVER variables are populated in global scope.
> So for example if variables_order is set to "EGPCS", register_globals is enabled,
> and both $_GET['action'] and  $_POST['action'] are set,
> then $action will contain the value of $_POST['action'] as P comes after G in our example directive value.



### $_REQUEST 覆盖

variables_order 与 request_order, 这两个指令影响导入$_REQUEST同名变量值得覆盖顺序;

variables_order: 不同顺序会引发引发$_REQUEST变量覆盖;默认GPC: post覆盖get,cookie覆盖post

[request_order](http://us2.php.net/manual/en/ini.core.php#ini.request-order)

> request_order
> This directive describes the order in which PHP registers GET, POST and Cookie variables into the _REQUEST array.
> Registration is done from left to right, newer values override older values.
> If this directive is not set, variables_order is used for $_REQUEST contents.
> Note that the default distribution php.ini files does not contain the 'C' for cookies, due to security concerns.
> 因为安全原因当前发行版本的php.ini文件无c选项


### 关于 $_EVN 与 getenv()

[ref](http://stackoverflow.com/questions/8798294/getenv-vs-env-in-php)

> Additionally $_ENV is typically empty if "variables_order"(php.ini) does't have E listed.
> $_ENV依赖variables_order配置导入
> On many setups it's likely that only $_SERVER is populated, and $_ENV is strictly for CLI usage.
> $_ENV只在CLI下可用
> On the other hand getenv() accesses the environment directly.
> getenv()直接访问当前环境
> (Regarding the case-ambiguity, one could more simply employ array_change_key_case().)


> According to the php documentation about getenv, they are exactly the same,
> except that getenv will look for the variable in a case-insensitive manner.
> getenv() 大小写不敏感
> Most of the time it probably doesn't matter, but one of the comments on the documentation explains:

> For example on Windows $_SERVER['Path'] is like you see, with the first letter capitalized,
> not 'PATH' as you might expect.
> Because of that, I would probably opt to use getenv unless you are
> certain about the casing of the title of the variable you are trying to retrieve.