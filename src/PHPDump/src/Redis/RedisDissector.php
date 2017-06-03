<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/5/31
 * Time: 下午3:23
 */

namespace Minimalism\PHPDump\Redis;


use Minimalism\PHPDump\Pcap\Connection;
use Minimalism\PHPDump\Pcap\Dissector;
use Minimalism\PHPDump\Pcap\PDU;

class RedisDissector implements Dissector
{

    public function getName()
    {
        return "Redis";
    }

    /**
     * @param Connection $connection
     * @return int DETECTED|UNDETECTED|DETECT_WAIT
     */
    public function detect(Connection $connection)
    {
        $buffer = $connection->buffer;
        if ($buffer->readableBytes() < 1) {
            return Dissector::DETECT_WAIT;
        }


        $t = $buffer->get(self::$maxMethodSize);

        if (substr($t, 0, 4) === "HTTP") {
            return Dissector::DETECTED;
        } else {
            foreach (self::$methods as $method => $len) {
                if (substr($t, 0, $len) === $method) {
                    return Dissector::DETECTED;
                }
            }
            return Dissector::UNDETECTED;
        }
    }

    /**
     * @param Connection $connection
     * @return bool
     */
    public function isReceiveCompleted(Connection $connection)
    {
        // TODO: Implement isReceiveCompleted() method.
    }

    /**
     * @param Connection $connection
     * @return PDU|null
     */
    public function dissect(Connection $connection)
    {
        // TODO: Implement dissect() method.
    }
}