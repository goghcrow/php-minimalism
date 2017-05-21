<?php

namespace Minimalism\FakeServer\MySQL;


use Minimalism\FakeServer\Buffer\BufferFactory;

class MySQLConnection
{
    private $fd;
    private $mysqlServer;
    private $state;

    private $inputBuffer;
    private $outputBuffer;
    private $seq;
    private $salt;

    public $remoteHost;
    public $username;
    public $charset;
    public $database;

    const STATE_BEFORE_GREET = 1;
    const STATE_BEFORE_LOGIN = 2;
    const STATE_AFTER_LOGIN = 3;
    const STATE_BEFORE_RESPONSE = 4;
    const STATE_AFTER_RESPONSE = 5;

    public function __construct(FakeMySQLServer $mysqlServer, $fd, $remoteHost)
    {
        $this->mysqlServer = $mysqlServer;
        $this->fd = $fd;
        $this->remoteHost = $remoteHost;

        $this->inputBuffer = new MySQLBinaryStream(BufferFactory::make());
        $this->outputBuffer = new MySQLBinaryStream(BufferFactory::make());
        $this->state = self::STATE_BEFORE_GREET;
        $this->seq = -1;

        $this->salt = openssl_random_pseudo_bytes(8);
    }

    public function action()
    {
        switch ($this->state) {
            case self::STATE_BEFORE_GREET:
                $this->state = self::STATE_BEFORE_LOGIN;
                $this->sendGreetingPacket();
                break;

            case self::STATE_BEFORE_LOGIN:
                $this->seq++;
                $loginData = $this->readAuthorizationPacket();
                $this->username = $loginData["username"];
                $this->charset = $loginData["charset"];
                $this->database = $loginData["schema"];
                $this->trigger("login", $this, $loginData);
                break;

            case self::STATE_AFTER_LOGIN:
            case self::STATE_AFTER_RESPONSE:
                $this->state = self::STATE_BEFORE_RESPONSE;
                $this->readCommand();
                break;

            case self::STATE_BEFORE_RESPONSE:
                fprintf(STDERR, "ERROR STATE\n");
                // TODO: server上一条消息未回复， 收到client下一条消息
                $this->close();
                break;

            default:
                assert(false);
        }
    }

    public function sendOK($message = "", $affectedRows = 0, $lastInsertId = 0, $warningCount = 0)
    {
        $this->outputBuffer->writeResponseOK($message, $affectedRows, $lastInsertId, $warningCount);
        $this->sendPacket();
        $this->resetSeqAndState();
    }

    public function sendError($message = "Unknown MySQL error", $errno = 2000, $sqlstate = "HY000")
    {
        $this->outputBuffer->writeResponseERR($message, $errno, $sqlstate);
        $this->sendPacket();
        $this->resetSeqAndState();
    }

    public function authorize($pwd)
    {
        // $this->remoteHost // $this->database // $this->salt
    }
    
    public function authorizeOK()
    {
        $this->sendOK();
        $this->state = self::STATE_AFTER_LOGIN;
    }

    public function authorizeERR()
    {
        $this->sendError("Authorization failed.", 1044, 28000);
        $this->mysqlServer->closeSession($this->fd);
    }

    public function sendErrorUnsupported($command)
    {
        $cmdName = $this->getCommandName($command);
        $this->sendError("Command $cmdName not supported.", 1235, 42000);
    }

    public function sendErrorPrepared($command)
    {
        $cmdName = $this->getCommandName($command);
        $this->sendError("Prepared statement command $cmdName not supported.", 1295, "HY100");
    }

    public function readPacket()
    {
        $len = $this->inputBuffer->tryReadPacketLen();
        if ($len > 0) {
            $this->action();
        }
        return $len;
    }

    public function getCommandName($cmd)
    {
        static $map;
        if ($map === null) {
            $clazz = new \ReflectionClass(MySQLCommand::class);
            $map = array_flip($clazz->getConstants());
        }

        if (isset($map[$cmd])) {
            return $map[$cmd];
        } else {
            return "Unknown CMD";
        }
    }

    /**
     * 当客户端发送查询请求后，在没有错误的情况下，服务器会返回结果集（Result Set）给客户端。
     *
     * @param MySQLField[] $fields
     * @param array $values
     * @param null $headerExt
     * @param bool $isBinData
     *
     * 结构    说明
     * [Result Set Header]    列数量
     * [Field]    列信息（多个）
     * [EOF]    列结束
     * [Row Data]    行数据（多个）
     * [EOF]    数据结束
     */
    public function sendResultSet(array $fields, array $values, $headerExt = null, $isBinData = false)
    {
        $this->outputBuffer->writeResultSetHeader(count($fields), $headerExt);
        $this->sendPacket();

        if ($fields) {
            foreach ($fields as $field) {
                $this->outputBuffer->writeField($field);
                $this->sendPacket();
            }
            $this->outputBuffer->writeEOF();
            $this->sendPacket();
        }

        if ($values) {
            foreach ($values as $rowValue) {
                if ($isBinData) {
                    $this->outputBuffer->writeRowDataBin($fields, $rowValue);
                } else {
                    $this->outputBuffer->writeRowData($fields, $rowValue);
                }
                $this->sendPacket();
            }
            $this->outputBuffer->writeEOF();
            $this->sendPacket();
        }

        $this->resetSeqAndState();
    }

    /**
     * 用于响应客户端发起的预处理语句报文，组成结构如下：
     *
     * @param int $stmtId
     * @param MySQLField[] $fields
     * @param MySQLField[] $parameters
     * @param int $warningCount
     *
     * 结构    说明
     * [PREPARE_OK]    PREPARE_OK结构
     * 如果参数数量大于0
     * [Field]    与Result Set消息结构相同
     * [EOF]
     * 如果列数大于0
     * [Field]    与Result Set消息结构相同
     * [EOF]
     */
    public function sendPrepare($stmtId, array $fields, array $parameters, $warningCount = 0)
    {
        $this->outputBuffer->writePrepareOK($stmtId, $fields, $parameters, $warningCount);
        $this->sendPacket();

        if ($parameters) {
            foreach ($parameters as $parameter) {
                $this->outputBuffer->writeParameter($parameter);
                $this->sendPacket();
            }
            $this->outputBuffer->writeEOF();
            $this->sendPacket();
        }

        if ($fields) {
            foreach ($fields as $field) {
                $this->outputBuffer->writeField($field);
                $this->sendPacket();
            }
            $this->outputBuffer->writeEOF();
            $this->sendPacket();
        }

        $this->resetSeqAndState();
    }

    public function close($force = false)
    {
        $swServ = $this->mysqlServer->swooleServer;
        if ($swServ->exist($this->fd)) {
            $swServ->close($this->fd, $force);
        }
    }

    public function write($bin)
    {
        return $this->inputBuffer->write($bin);
    }

    public function setDatabase($database)
    {
        $this->database = $database;
    }

    private function sendGreetingPacket()
    {
        $this->outputBuffer->writeGreetingPacket($this->salt);
        $this->sendPacket();
    }

    private function readAuthorizationPacket()
    {
        return $this->inputBuffer->readAuthorizationPacket();
    }

    private function readCommand()
    {
        list($cmd, $args) = $this->inputBuffer->readCommand();
        switch ($cmd) {
            case MySQLCommand::COM_QUIT:
                $this->mysqlServer->closeSession($this->fd);
                break;

            case MySQLCommand::COM_QUERY:
                $this->trigger("query", $this, $args["sql"]);
                break;

            case MySQLCommand::COM_PING:
                $this->sendOK();
                break;

            case MySQLCommand::COM_INIT_DB:
                $database = $args["database"];
                if ($this->trigger("query", $this, "USE $database")) {
                    $this->database = $database;
                }
                break;

            case MySQLCommand::COM_CREATE_DB:
                $database = $args["database"];
                $this->trigger("query", $this, "CREATE DATABASE $database");
                break;

            case MySQLCommand::COM_DROP_DB:
                $database = $args["database"];
                $this->trigger("query", $this, "DROP DATABASE $database");
                break;

            default:
                $this->trigger("command", $this, $cmd, $args);
        }
    }

    private function trigger($evtName, ...$args)
    {
        $evHandler = $this->mysqlServer->eventHandler;
        if (isset($evHandler[$evtName])) {
            $action = $evHandler[$evtName];
            $action(...$args);
        } else {
            $this->sendError("Internal Error");
        }
    }

    private function sendPacket()
    {
        $this->outputBuffer->prependHeader(++$this->seq);
        return $this->mysqlServer->swooleServer->send($this->fd, $this->outputBuffer->readFull());
    }

    private function resetSeqAndState()
    {
        $this->seq = 0;
        $this->state = self::STATE_AFTER_RESPONSE;
    }
}