<?php

/**
 * class swoole_mysql
 * 异步 MySql 客户端
 *
 * @since 3.0.4
 *
 * @package swoole_mysql
 */

/**
 * class swoole_mysql
 *
 * @since 3.0.4
 */
class swoole_mysql
{
    /**
     * @var bool 是否连接上了MySQL服务器
     */
    public $connected;

    /**
     * @var int MySQL服务器返回的错误码
     */
    public $errno = 0;

    /**
     * @var string MySQL服务器返回的错误信息
     */
    public $error = "";

    /**
     * @var string 发生在sock上的连接错误信息
     */
    public $connect_error = "";

    /**
     * @var string 发生在sock上的连接错误码
     */
    public $connect_errno = 0;

    /**
     * @var int 影响的行数
     */
    public $affected_rows = 0;

    /**
     * @var int 最后一个插入的记录id
     */
    public $insert_id = -1;

    /**
     * __construct mysql 客户端构造函数
     *
     * @since 3.0.4
     *
     */
    public function __construct()
    {
    }

    /**
     * __destruct 析构函数
     *
     * @since 3.0.4
     * @internal
     *
     */
    public function __destruct()
    {
    }

    /**
     * isConnected 判断连接是否正常
     *
     * @since 3.0.4
     *
     *@return bool true 处于连接状态，false 非连接状态
     */
    public function isConnected()
    {
    }
    
    /**
     * setConnectTimeout 设置连接超时时间,可重复设置，以最后一次设置为准
     *
     * @since 3.0.4
     *
     * @param int $timeout 超时时间，单位ms，设置为0时，不超时
     * @return bool 设置成功返回true，设置失败返回false
     */
    public function setConnectTimeout(int $timeout) { }
    
    /**
     * setQueryTimeout 设置消息发送超时时间,可重复设置，以最后一次设置为准，仅对query生效
     *
     * @since 3.0.4
     *
     * @param int $timeout 超时时间，单位ms，设置为0时，不超时
     * @return bool 设置成功返回true，设置失败返回false
     */
    public function setQueryTimeout(int $timeout) { }
    
    /**
     * connect 异步连接到 MySql 服务器
     *
     * @since 3.0.4
     *
     * @param array    $server_config MySql 服务器的配置，必须为关联索引数组
     ```php
     * $server_config = array(
     *      'host' => '127.0.0.1',  // MySQL服务器的主机地址，支持IPv6（::1）和UnixSocket（unix:/tmp/mysql.sock）
     *      'port' => 3306,         // MySQL服务器监听的端口，可选，默认为3306
     *      'user' => 'root',       // 用户名，必填
     *      'password' => '123456', // 密码，必填
     *      'database' => 'test',   // 要连接的数据库，必填
     *      'timeout' => '2.0',     // 超时时间，可选，浮点数，默认值为 1.0 秒
     *      'charset' => 'utf8',    // 设置客户端字符集，可选，默认使用 Server 返回的字符集；不存在会抛出异常
     * );
     * ```
     * @param callable $callback 连接完成后回调此函数,函数原型参考onConnect
     *```php
     *  连接事件回调函数
     *  param swoole_mysql $db  mysql对象
     *  param bool         $result连接结果，true，连接成功，false连接失败
     *  
     *  function onConnect(swoole_mysql $db, bool $result);
     * ```
     * @return 成功返回 true，失败返回 false
     */
    public function connect(array $server_config, callable $callback)
    {
    }

    /**
     * query 执行 Sql 查询语句，每个 mysql 连接只能同时执行一条 sql 语句，必须等待返回结果后才能执行下一条 sql
     *
     * @since 3.0.4
     *
     * @param string   $sql 要执行的 sql 查询语句
     * @param callable $callback 执行成功后回调此函数，函数原型参见onSQLReady
     * ```php
     *  onSQLReady mysql语句执行结果回调
     *  param swoole_mysqli $link mysql对象
     *  param $result  false|true|array
     *  			   false: 执行失败，可通过$link对象的error属性获得错误信息，errno属性获得错误码；
     *  			   true:  执行非查询语句结果，读取$link对象的affected_rows属性获得影响的行数，insert_id属性获得Insert操作的自增ID；
     *   			   arrary:执行查询语句结果，$result为结果数组.
     *  
     *  function onSQLReady(swoole_mysqli $link, mixed $result);
     *  ```               
     * 
     * @return bool 执行成功返回 true，失败返回 false
     */
    public function query(string $sql, callable $callback)
    {
    }

    /**
     * close 关闭MySql连接
     *
     * @since 3.0.4
     *
     * @return bool
     */
    public function close()
    {
    }

    /**
     * on 注册异步 MySql 客户端事件回调函数，必须在 connect 前被调用
     *
     * @since 3.0.4
     *
     * @param string   $event_name 事件名称，事件类型
     * ```php
     * "close"      // 连接关闭事件
     * ```
     * @param callable $callback 事件回调函数,函数原型参见onClose
     * ```php
     * 连接关闭事件回调
     * param swoole_mysql $db  mysql对象
     * function onClose(swoole_mysql $db);
     * ```
     *                 
     * @return bool 成功返回 true，失败返回 false
     */
    public function on(string $event_name, callable $callback)
    {
    }

    /**
     * begin，启动一个事务
     *
     * @since 3.0.4
     *
     * @param callable $callback  /// 必选，如onBegin($swoole_sql), $result == true: 执行成功;$result == false: 执行失败；
     * @return false 接口调用失败，true 接口调用成功
     */
    public function begin(callable $callback) {}
    
    public function safe_query(string $sql, array $bindparam,callable $callback) {}
    
    /**
     * rollback，回滚由begin发起的当前事务
     *
     * @since 3.0.4
     *
     * @param callable $callback  /// 必选，如onRollback($swoole_sql), $result == true: 执行成功;$result == false: 执行失败；
     * @return false 接口调用失败，true 接口调用成功
     */
    public function rollback(callable $callback) { }
    
    /**
     * commit，提交事务
     *
     * @since 3.0.4
     *
     * @param callable $callback  /// 必选，如onCommit($swoole_sql), $result == true: 执行成功;$result == false: 执行失败；
     * @return false 接口调用失败，true 接口调用成功
     */
    public function commit() { }
    
    /**
     * isUsedindex，判断当前查询是否使用索引
     *
     * @since 3.0.4
     *
     * @return false 当前查询未使用索引，true 当前查询使用了索引
     */
    public function isUsedindex() {}
    
    /**
     * 转义SQL语句中的特殊字符，避免 SQL 注入攻击。底层基于 mysqlnd 提供的函数实现，需要依赖 PHP 的 mysqlnd 扩展。
     * 编译时需要增加 --enable-mysqlnd 来启用，如果PHP环境中没有 mysqlnd 将会出现编译错误
     * @param string $str 要转义的字符串
     *```php
     * $db->connect($server, function ($db, $result) {
     *      $data = $db->escape("abc'efg\r\n");
     * });
     * ```
     * @return string|false 成功返回转义后的 string，失败返回 false
     */
    public function escape(string $str)
    {
    }
}
