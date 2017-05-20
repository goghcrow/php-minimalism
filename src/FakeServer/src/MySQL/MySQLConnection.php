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

    const STATE_BEFORE_GREET = 1;
    const STATE_BEFORE_LOGIN = 2;
    const STATE_AFTER_LOGIN = 3;

    public function __construct(FakeMySQLServer $mysqlServer, $fd)
    {
        $this->mysqlServer = $mysqlServer;
        $this->fd = $fd;

        $this->inputBuffer = new MySQLBinaryStream(BufferFactory::make());
        $this->outputBuffer = new MySQLBinaryStream(BufferFactory::make());
        $this->state = self::STATE_BEFORE_GREET;
        $this->seq = -1;
    }

    public function action()
    {
        switch ($this->state) {
            case self::STATE_BEFORE_GREET:
                // TODO join('',map { chr(int(33 + rand(94))) } (1..20))
                $salt = openssl_random_pseudo_bytes(8); // TODO 8 ? 20
                $this->sendGreetingPacket($salt);
                $this->state = self::STATE_BEFORE_LOGIN;
                break;

            case self::STATE_BEFORE_LOGIN:
                $this->seq++;
                $loginData = $this->readAuthorizationPacket();
                $this->trigger("login", $this, $loginData);
                break;

            case self::STATE_AFTER_LOGIN:
                $this->readCommand();
                break;

            default:
                assert(false);
        }
    }

    public function authorizeOK()
    {
        $this->responseOK();
        $this->state = self::STATE_AFTER_LOGIN;
    }

    public function authorizeERR()
    {
        $this->responseERR("Authorization failed.", 1044, 28000);
        $this->mysqlServer->closeSession($this->fd);
    }

    public function responseOK($message = "", $affectedRows = 0, $lastInsertId = 0, $warningCount = 0)
    {
        $this->outputBuffer->writeResponseOK($message, $affectedRows, $lastInsertId, $warningCount);
        $this->sendPacket();
        $this->resetSeq();
    }

    public function responseERR($message = "Unknown MySQL error", $errno = 2000, $sqlstate = "HY000")
    {
        $this->outputBuffer->writeResponseERR($message, $errno, $sqlstate);
        $this->sendPacket();
        $this->resetSeq();
    }

    private function sendGreetingPacket($salt)
    {
        $this->outputBuffer->writeGreetingPacket($salt);
        $this->sendPacket();
    }

    public function readPacket()
    {
        $len = $this->inputBuffer->tryReadPacketLen();
        if ($len > 0) {
            $this->action();
        }
        return $len;
    }

    private function readAuthorizationPacket()
    {
        return $this->inputBuffer->readAuthorizationPacket();
    }

    private function readCommand()
    {
        list($cmd, $args) = $this->inputBuffer->readCommand();
        switch ($cmd) {
            case MySQLCommand::COM_QUERY:
                $this->trigger("query", $this, $args["sql"]);
                break;

            default:
                $this->trigger("command", $this, $cmd, $args);
        }
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
    public function writeResultSet(array $fields, array $values, $headerExt = null, $isBinData = false)
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

        $this->resetSeq();
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
    public function writePrepare($stmtId, array $fields, array $parameters, $warningCount = 0)
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

        $this->resetSeq();
    }

    public function write($bin)
    {
        return $this->inputBuffer->write($bin);
    }

    private function resetSeq()
    {
        return $this->seq = 0;
    }

    public function close($force = false)
    {
        $swServ = $this->mysqlServer->swooleServer;
        if ($swServ->exist($this->fd)) {
            $swServ->close($this->fd, $force);
        }
    }

    public function send($data)
    {
        return $this->mysqlServer->swooleServer->send($this->fd, $data);
    }

    public function trigger($evtName, ...$args)
    {
        $action = $this->mysqlServer->eventHandler[$evtName];
        $action(...$args);
    }

    /**
     * header: 3bytes len + 1byte seq
     * body: n bytes
     *
     * len: 用于标记当前请求消息的实际数据长度值，以字节为单位，占用3个字节，最大值为 0xFFFFFF，即接近 16 MB 大小（比16MB少1个字节）。
     * seq: 在一次完整的请求/响应交互过程中，用于保证消息顺序的正确，每次客户端发起请求时，序号值都会从0开始计算。
     * body: 消息体用于存放请求的内容及响应的数据，长度由消息头中的长度值决定。
     */
    private function sendPacket()
    {
        $this->outputBuffer->prependHeader(++$this->seq);
        $this->send($this->outputBuffer->readFull());
    }
}