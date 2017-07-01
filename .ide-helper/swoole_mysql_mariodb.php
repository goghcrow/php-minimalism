<?php

/**
 * swoole_mysql
 *
 * @since 2.0.0
 *
 * @package
 */
class swoole_mysql
{
    /**
     * 连接使用的文件描述符
     * @var int
     */
    public $sock;

    /**
     * 是否连接上了MySQL服务器
     * @var bool
     */
    public $connected;

    /**
     * 最后一次错误
     * @link http://php.net/manual/en/mysqli.errno.php
     * @var int
     */
    public $errno;

    /**
     * 最后一次错误的描述
     * @link http://php.net/manual/en/mysqli.error.php
     * @var string
     */
    public $error;

    /**
     * 影响的行数
     * @link http://php.net/manual/en/mysqli.affected-rows.php
     * @var int
     */
    public $affected_rows;

    /**
     * 最后一个插入的记录id
     * @link http://php.net/manual/en/mysqli.insert-id.php
     * @var int
     */
    public $insert_id;


    /**
     * __construct
     *
     * @since 2.0.0
     */
    public function __construct() {}

    /**
     * query
     *
     * @since 2.0.0
     * @link http://php.net/manual/en/mysqli.query.php
     * @param string $sql
     * @param array $bindValues
     * @param callable $queryCallback
     *   void onQueryCompleted(SwooleMysql $swooleMysql, array $result)
     * @return bool
     *
     */
    public function query($sql, array $bindValues, callable $queryCallback) {}

    /**
     * 关闭mysql连接
     *
     * @since 2.0.0
     * @link http://php.net/manual/en/mysqli.close.php
     * @return void
     */
    public function close() {}

    /**
     * connect
     *
     * @since 2.0.0
     * @param array $serverInfo
     * ```php
     * [
     * 'host' => 'MySQL IP地址',
     * 'port' => 'MySQL port',
     * 'user' => '数据用户',
     * 'password' => '数据库密码',
     * 'database' => '数据库名',
     * 'charset' => '字符集',
     * ];
     * ```
     *
     * @return bool
     *
     */
    public function connect(array $serverInfo) {}

    /**
     * on
     *
     * @since 2.0.0
     * @param string $eventName
     * 1. connect 连接成功 void onConnected(SwooleMysql $swooleMysql)
     * 2. error 连接失败 void onError(SwooleMysql $swooleMysql)
     * 3. close 连接关闭 void onClosed(SwooleMysql $swooleMysql)
     *
     * @param callable $callback
     * @return bool
     *
     */
    public function on($eventName, callable $callback) {}

    /**
     * begin
     *
     * @since 2.0.0
     * @link http://php.net/manual/en/mysqli.begin-transaction.php
     * @param callable $callback
     *   void onBeginTransactionCompleted(SwooleMysql $swooleMysql)
     *
     */
    public function begin(callable $callback) {}

    /**
     * commit
     *
     * @since 2.0.0
     * @link http://php.net/manual/en/mysqli.commit.php
     * @param callable $callback
     *   void onCommitCompleted(SwooleMysql $swooleMysql)
     *
     */
    public function commit(callable $callback) {}

    /**
     * rollback
     *
     * @since 2.0.0
     * @link http://php.net/manual/en/mysqli.rollback.php
     * @param callable $callback
     *   void onRollbackCompleted(SwooleMysql $swooleMysql)
     *
     */
    public function rollback(callable $callback) {}


    /**
     * escape_string
     *
     * @since 2.0.0
     * @link http://php.net/manual/en/mysqli.real-escape-string.php
     * @param string $escapestr
     * @return string
     */
    public function escape_string($escapestr) {}

}

