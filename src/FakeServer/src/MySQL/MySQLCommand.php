<?php

namespace Minimalism\FakeServer\MySQL;


/**
 * Interface MySQLCommand
 * @package Minimalism\FakeServer\MySQL
 *
 * https://dev.mysql.com/doc/dev/mysql-server/latest/my__command_8h_source.html
 */
interface MySQLCommand
{
    const COM_SLEEP               = 0x00; //（内部线程状态）    （无）
    const COM_QUIT                = 0x01; // 关闭连接    mysql_close
    const COM_INIT_DB             = 0x02; // 切换数据库   mysql_select_db
    const COM_QUERY               = 0x03; // SQL查询请求 mysql_real_query
    const COM_FIELD_LIST          = 0x04; // 获取数据表字段信息   mysql_list_fields
    const COM_CREATE_DB           = 0x05; // 创建数据库   mysql_create_db
    const COM_DROP_DB             = 0x06; // 删除数据库   mysql_drop_db
    const COM_REFRESH             = 0x07; // 清除缓存    mysql_refresh
    const COM_SHUTDOWN            = 0x08; // 停止服务器   mysql_shutdown
    const COM_STATISTICS          = 0x09; // 获取服务器统计信息   mysql_stat
    const COM_PROCESS_INFO        = 0x0A; // 获取当前连接的列表   mysql_list_processes
    const COM_CONNECT             = 0x0B; // （内部线程状态）    （无）
    const COM_PROCESS_KILL        = 0x0C; // 中断某个连接  mysql_kill
    const COM_DEBUG               = 0x0D; // 保存服务器调试信息   mysql_dump_debug_info
    const COM_PING                = 0x0E; // 测试连通性   mysql_ping
    const COM_TIME                = 0x0F; // （内部线程状态）    （无）
    const COM_DELAYED_INSERT      = 0x10; // （内部线程状态）    （无）
    const COM_CHANGE_USER         = 0x11; // 重新登陆（不断连接）  mysql_change_user
    const COM_BINLOG_DUMP         = 0x12; // 获取二进制日志信息   （无）
    const COM_TABLE_DUMP          = 0x13; // 获取数据表结构信息   （无）
    const COM_CONNECT_OUT         = 0x14; // （内部线程状态）    （无）
    const COM_REGISTER_SLAVE      = 0x15; // 从服务器向主服务器进行注册   （无）
    const COM_STMT_PREPARE        = 0x16; // 预处理SQL语句    mysql_stmt_prepare
    const COM_STMT_EXECUTE        = 0x17; // 执行预处理语句 mysql_stmt_execute
    const COM_STMT_SEND_LONG_DATA = 0x18; // 发送BLOB类型的数据 mysql_stmt_send_long_data
    const COM_STMT_CLOSE          = 0x19; // 销毁预处理语句 mysql_stmt_close
    const COM_STMT_RESET          = 0x1A; // 清除预处理语句参数缓存 mysql_stmt_reset
    const COM_SET_OPTION          = 0x1B; // 设置语句选项  mysql_set_server_option
    const COM_STMT_FETCH          = 0x1C; // 获取预处理语句的执行结果    mysql_stmt_fetch
    const COM_DAEMON              = 0x1D;
    const COM_BINLOG_DUMP_GTID    = 0x1E;
    const COM_RESET_CONNECTION    = 0x1F;
    const COM_END                 = 0x20;

    public function onSleep();                          //
    public function onQuit();                           // 关闭当前连接（客户端退出），无参数。
    public function onInitDB($database);                // 切换数据库，对应的SQL语句为USE <database>。
    public function onQuery($sql);                      // 最常见的请求消息类型，当用户执行SQL语句时发送该消息。
    public function onFieldList($table, $column);       // 查询某表的字段（列）信息，等同于SQL语句SHOW [FULL] FIELDS FROM ...
    public function onCreateDB($database);              // 创建数据库，该消息已过时，而被SQL语句CREATE DATABASE代替
    public function onDropDB($database);                // 删除数据库，该消息已过时，而被SQL语句DROP DATABASE代替。
    public function onRefresh($flags);                  // 清除缓存，等同于SQL语句FLUSH，或是执行mysqladmin flush-foo命令时发送该消息
    public function onShutdown($flags);                 // 停止MySQL服务。执行mysqladmin shutdown命令时发送该消息。
    public function onStatistics();                     // 查看MySQL服务的统计信息（例如运行时间、每秒查询次数等）。执行mysqladmin status命令时发送该消息，无参数。
    public function onProcessInfo();                    // 获取当前活动的线程（连接）列表。等同于SQL语句SHOW PROCESSLIST，或是执行mysqladmin processlist命令时发送该消息，无参数。
    public function onConnect();                        //
    public function onProcessKill($connId);             // 要求服务器中断某个连接。等同于SQL语句KILL <id>。
    public function onDebug();                          // 要求服务器将调试信息保存下来，保存的信息多少依赖于编译选项设置（debug=no|yes|full）。执行mysqladmin debug命令时发送该消息，无参数。
    public function onPing();                           // 该消息用来测试连通性，同时会将服务器的无效连接（超时）计数器清零。执行mysqladmin ping命令时发送该消息，无参数。
    public function onTime();                           //
    public function onDelayedInsert();                  //
    public function onChangeUser($username, $password, $database, $charset);            // 在不断连接的情况下重新登陆，该操作会销毁MySQL服务器端的会话上下文（包括临时表、会话变量等）。有些连接池用这种方法实现清除会话上下文。
    public function onBinlogDump($offset, $flags, $slaveId, $fileName);                  // TODO 该消息是备份连接时由从服务器向主服务器发送的最后一个请求，主服务器收到后，会响应一系列的报文，每个报文都包含一个二进制日志事件。如果主服务器出现故障时，会发送一个EOF报文。
    public function onTableDump($database, $table);     // 将数据表从主服务器复制到从服务器中，执行SQL语句LOAD TABLE ... FROM MASTER时发送该消息。目前该消息已过时，不再使用。
    public function onConnectOut();                     //
    public function onRegisterSlave($slaveId, $masterIP, $masterUsername, $masterPassword, $masterPort, $level, $masterId);  // 在从服务器report_host变量设置的情况下，当备份连接时向主服务器发送的注册消息。
    public function onStmtPrepare($sql);                // 预处理SQL语句，使用带有"?"占位符的SQL语句时发送该消息。
    public function onStmtExecute(/* TODO */);          // 执行预处理语句。
    public function onStmtSendLongData($stmtId, $argId, $payload);               // 该消息报文有两种形式，一种用于发送二进制数据，另一种用于发送文本数据。
                                                        // 用于发送二进制（BLOB）类型的数据（调用mysql_stmt_send_long_data函数）
                                                        // 用于发送超长字符串类型的数据（调用mysql_send_long_data函数）
    public function onStmtClose($stmtId);               // 销毁预处理语句。
    public function onStmtRest($stmtId);                // 将预处理语句的参数缓存清空。多数情况和COM_LONG_DATA一起使用。
    public function onSetOption($flags);                // 设置语句选项，选项值为/include/mysql_com.h头文件中定义的enum_mysql_set_option枚举类型：
    public function onStmtFetch($stmtId, $rows);        // 获取预处理语句的执行结果（一次可以获取多行数据）。
}