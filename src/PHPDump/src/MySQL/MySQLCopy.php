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
        if ($mySQLPacket->pktType === MySQLPDU::PKT_CMD) {
            list($cmd, $args) = $mySQLPacket->payload;
            if ($cmd === MySQLCommand::COM_QUERY) {
                $sql = $args["sql"];
                if ($sql) {
                    swoole_async_write($this->file, $sql . "\n", -1);
                }
            }
        }
    }
}