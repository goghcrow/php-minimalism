# Config

## Yaconf & Converter

1. php版本Yaconf，用于swoole server长生命周期模型
2. Converter：数组配置与ini配置转换

## Config & ConfigGen

### 1. Intro

1. 参考Zan Framework的Config模块功能重写
2. 可根据加载配置自动生成对象属性读写代码, 方便IDE提示


### 2. 配置组织结构示例

```
以mysql为例,见项目Conf目录

./example/Core/Config/Conf
├── dev
│   └── store
│       └── mysql.php
├── product
│   └── store
│       └── mysql.php
├── share
│   └── dict.php
└── test
    └── store
        └── mysql.php
```

按照环境组织配置文件, 示例有dev,test,product三个环境,share是共享配置

三个环境的配置文件会共享share下的配置,且优先级高于share下,同名key覆盖

默认特定目录加载*.php作为配置文件,所有配置文件约定返回数组


### 3. 配置示例

mysql.php

```php
return [
    "share" => [
        "port" => 3306,
        "user" => "dev_user",
    ],
    "master" => [
        "host" => "192.168.0.1",
        "password" => "pwd_master",
    ],
    "cluster1" => [
        "host" => "192.168.0.1",
        "password" => "pwd_cluster1",
    ],
    "cluster2" => [
        "host" => "192.168.0.1",
        "port" => "3307",
        "password" => "pwd_cluster2",
    ],
];
```

share项目为默认配置

所有子项继承share项目配置, 同名key覆盖share

dict.php

```php
return [
    "App" => "Config",
    "Version" => "0.1",
    "reference" => "Zan-config",
    "coder" => "xiaofeng",
];
```


加载dev环境的配置, get()的结果如下:
```php
Config::load(__DIR__ . "/Config", "dev");
Config::get()

Array
(
    [dict] => Array
        (
            [App] => Config
            [Version] => 0.1
            [reference] => Zan-config
            [coder] => xiaofeng
        )

    [store] => Array
        (
            [mysql] => Array
                (
                    [master] => Array
                        (
                            [port] => 3306
                            [user] => dev_user
                            [host] => 192.168.0.1
                            [password] => pwd_master
                        )

                    [cluster1] => Array
                        (
                            [port] => 3306
                            [user] => dev_user
                            [host] => 192.168.0.1
                            [password] => pwd_cluster1
                        )

                    [cluster2] => Array
                        (
                            [port] => 3307
                            [user] => dev_user
                            [host] => 192.168.0.1
                            [password] => pwd_cluster2
                        )

                )

        )

)
```

### 4. 代码

**Config 配置加载与读取**

```php
<?php

namespace Minimalism\Test\Config;


use Minimalism\Config\Config;

require __DIR__ . "/../../src/Config/Config.php";


Config::load(__DIR__ . "/ExampleConfig", "dev");

assert(Config::get());
assert(Config::get("store.mysql.master.port") === 3306);
assert(Config::get("store.mysql.cluster1.password") === "pwd_cluster1");
assert(Config::get("dict.App") === "Config");

Config::set("store.mysql.cluster1.password", "new_password");
assert(Config::get("store.mysql.cluster1.password") === "new_password");

Config::set("dict.x.y.not_exist", "hello");
assert(Config::get("dict.x.y.not_exist") === "hello");

Config::load(__DIR__ . "/ExampleConfig", "test");
assert(Config::get());
assert(Config::get("store.mysql.master.user") === "test_user");
assert(Config::get("dict.App") === "Config");

Config::load(__DIR__ . "/ExampleConfig", "product");
assert(Config::get());
assert(Config::get("store.mysql.master.user") === "product_user");
assert(Config::get("dict.App") === "Config");
```

**ConfigGen 配置对象属性代码生成**

```php
<?php

namespace Minimalism\Test\Config;


use Minimalism\Config\Config;
use Minimalism\Config\ConfigGen;

require __DIR__ . "/../../src/Config/Config.php";
require __DIR__ . "/../../src/Config/ConfigGen.php";

Config::load(__DIR__ . "/ExampleConfig", "dev");

$file = "ConfigObject";
$conf = ConfigGen::requireOnce(Config::get(), __DIR__, "ConfigObject", __NAMESPACE__);

assert($conf);
assert($conf->store->mysql->master->port === 3306);
assert($conf->store->mysql->cluster1->password === "pwd_cluster1");
assert($conf->dict->App === "Config");

$conf->store->mysql->cluster1->password = "new_password";
assert($conf->store->mysql->cluster1->password === "new_password");

```

**自动生成的代码**

```php
<?php
/**
 * Auto Generated by ConfigGen
 * !!! DO NOT EDIT UNLESS YOU ARE SURE THAT YOU KNOW WHAT YOU ARE DOING
 * @generated
 */
namespace Minimalism\Test\Config;

final class ConfigObject_dict {

	public $App = 'Config';
	public $Version = '0.1';
	public $reference = 'Zan-config';
	public $coder = 'xiaofeng';

	public function __construct() {

	}
}

final class ConfigObject_store_mysql_master {

	public $port = 3306;
	public $user = 'dev_user';
	public $host = '192.168.0.1';
	public $password = 'pwd_master';

	public function __construct() {

	}
}

final class ConfigObject_store_mysql_cluster1 {

	public $port = 3306;
	public $user = 'dev_user';
	public $host = '192.168.0.1';
	public $password = 'pwd_cluster1';

	public function __construct() {

	}
}

final class ConfigObject_store_mysql_cluster2 {

	public $port = 3307;
	public $user = 'dev_user';
	public $host = '192.168.0.1';
	public $password = 'pwd_cluster2';

	public function __construct() {

	}
}

final class ConfigObject_store_mysql {

	public $master;
	public $cluster1;
	public $cluster2;

	public function __construct() {
		$this->master = new ConfigObject_store_mysql_master;
		$this->cluster1 = new ConfigObject_store_mysql_cluster1;
		$this->cluster2 = new ConfigObject_store_mysql_cluster2;
	}
}

final class ConfigObject_store {

	public $mysql;

	public function __construct() {
		$this->mysql = new ConfigObject_store_mysql;
	}
}

final class ConfigObject {

	public $dict;
	public $store;

	public function __construct() {
		$this->dict = new ConfigObject_dict;
		$this->store = new ConfigObject_store;
	}
}

return new ConfigObject;
```