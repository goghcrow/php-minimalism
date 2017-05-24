<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/5/22
 * Time: 下午11:44
 */

namespace Minimalism\PHPDump\MySQL;


class MySQLCopy
{
    public $file;

    public function __construct($file)
    {
        $this->file = $file;
    }

    public function __invoke(MySQLPDU $mySQLPacket)
    {
        if (/*$mySQLPacket->packetType === MySQLBinaryStream::PACKET_TYPE_CMD
            && */$mySQLPacket->cmdType === MySQLCommand::COM_QUERY) {
            $sql = $mySQLPacket->payload;
            swoole_async_write($this->file, $sql, -1);
        }
    }
}