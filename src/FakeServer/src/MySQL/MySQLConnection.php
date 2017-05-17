<?php

namespace Minimalism\FakeServer\MySQL;


use Minimalism\FakeServer\Buffer\BufferFactory;

class MySQLConnection
{
    private $fd;
    private $mysqlServer;
    private $state;

    public $inputBuffer;
    public $outputBuffer;

    const STATE_BEFORE_GREET = 1;
    const STATE_WAIT_LOGIN = 2;
    const STATE_AFTER_AUTHORIZE = 3;

    public function __construct(FakeMySQLServer $mysqlServer, $fd)
    {
        $this->mysqlServer = $mysqlServer;
        $this->fd = $fd;

        $this->inputBuffer = BufferFactory::make();
        $this->outputBuffer = BufferFactory::make();
        $this->state = self::STATE_BEFORE_GREET;
    }

    public function action()
    {
        switch ($this->state) {
            case self::STATE_BEFORE_GREET:
                $this->onGreet();
                break;

            case self::STATE_WAIT_LOGIN:
                $this->onLogin();
                break;

            case self::STATE_AFTER_AUTHORIZE:
                // ....
                break;

            default:
                assert(false);
        }
    }

    private function onLogin()
    {
        $vars = MySQLProtocol::unpackLoginPacket($this->inputBuffer);

        $action = $this->mysqlServer->eventHandler["login"];
        if ($action($vars)) {
            MySQLProtocol::packResponseOK($this->outputBuffer);
            $this->send();
            $this->state = self::STATE_AFTER_AUTHORIZE;
        } else {
            // err
            // TODO
//            MySQLProtocol::packResponseKO($this->outputBuffer);
//            $this->send();

            // close
            $this->mysqlServer->swooleServer->close($this->fd);
        }
    }

    private function onGreet()
    {
        MySQLProtocol::packGreeting($this->outputBuffer);
        $this->send();
        $this->state = self::STATE_WAIT_LOGIN;
    }

    private function send()
    {
        list($header, $body) = MySQLProtocol::packHeader($this->outputBuffer);

        $this->mysqlServer->swooleServer->send($this->fd, $header);
        $this->mysqlServer->swooleServer->send($this->fd, $body);
    }
}