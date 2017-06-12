<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/5/31
 * Time: 下午3:22
 */

namespace Minimalism\PHPDump\Redis;


use Minimalism\PHPDump\Pcap\Connection;
use Minimalism\PHPDump\Pcap\PDU;
use Minimalism\PHPDump\Util\T;

/**
 * Class RedisPDU
 * @package Minimalism\PHPDump\Redis
 *
 * @ref https://redis.io/topics/protocol
 * @ref http://redisdoc.com/topic/protocol.html
 */
class RedisPDU extends PDU
{
    const MSG_STATUS = '+';
    const MSG_ERROR = '-';
    const MSG_INTEGER = ':';
    const MSG_BULK = '$';
    const MSG_MULTI = '*';

    public $msgType;
    public $payload;

    const CMD_LIST = '["module","get","set","setnx","setex","psetex","append","strlen","del","unlink","exists","setbit","getbit","bitfield","setrange","getrange","substr","incr","decr","mget","rpush","lpush","rpushx","lpushx","linsert","rpop","lpop","brpop","brpoplpush","blpop","llen","lindex","lset","lrange","ltrim","lrem","rpoplpush","sadd","srem","smove","sismember","scard","spop","srandmember","sinter","sinterstore","sunion","sunionstore","sdiff","sdiffstore","smembers","sscan","zadd","zincrby","zrem","zremrangebyscore","zremrangebyrank","zremrangebylex","zunionstore","zinterstore","zrange","zrangebyscore","zrevrangebyscore","zrangebylex","zrevrangebylex","zcount","zlexcount","zrevrange","zcard","zscore","zrank","zrevrank","zscan","hset","hsetnx","hget","hmset","hmget","hincrby","hincrbyfloat","hdel","hlen","hstrlen","hkeys","hvals","hgetall","hexists","hscan","incrby","decrby","incrbyfloat","getset","mset","msetnx","randomkey","select","swapdb","move","rename","renamenx","expire","expireat","pexpire","pexpireat","keys","scan","dbsize","auth","ping","echo","save","bgsave","bgrewriteaof","shutdown","lastsave","type","multi","exec","discard","sync","psync","replconf","flushdb","flushall","sort","info","monitor","ttl","touch","pttl","persist","slaveof","role","debug","config","subscribe","unsubscribe","psubscribe","punsubscribe","publish","pubsub","watch","unwatch","cluster","restore","restore-asking","migrate","asking","readonly","readwrite","dump","object","memory","client","eval","evalsha","slowlog","script","time","bitop","bitcount","bitpos","wait","command","geoadd","georadius","georadiusbymember","geohash","geopos","geodist","pfselftest","pfadd","pfcount","pfmerge","pfdebug","post","host:","latency"]';

    public static function getName($msgType)
    {
        $cache = null;
        if ($cache === null) {
            $clazz = new \ReflectionClass(static::class);
            $valueNames = array_flip($clazz->getConstants());
        }
        if (isset($valueNames[$msgType])) {
            return $valueNames[$msgType];
        } else {
            return "UNKNOWN";
        }
    }

    public static function isRedisCmd($cmd)
    {
        static $cache;
        if ($cache === null) {
            $cache = array_flip(json_decode(static::CMD_LIST));
        }

        $cmd = strtolower($cmd);
        return isset($cache[$cmd]);
    }

    /**
     * @param Connection $connection
     */
    public function inspect(Connection $connection)
    {
        $sec = $connection->recordHdr->ts_sec;
        $usec = $connection->recordHdr->ts_usec;

        $srcIp = $connection->IPHdr->source_ip;
        $dstIp = $connection->IPHdr->destination_ip;
        $srcPort = $connection->TCPHdr->source_port;
        $dstPort = $connection->TCPHdr->destination_port;

        $src = T::format("$srcIp:$srcPort", T::BRIGHT);
        $dst = T::format("$dstIp:$dstPort", T::BRIGHT);

        $msgType = static::getName($this->msgType);
        $msgType = T::format($msgType, T::BRIGHT);

        $direction = $this->isRequest() ? "REQUEST" : "RESPONSE";
        $direction = T::format($direction, T::FG_YELLOW, T::BRIGHT);

        sys_echo("$src > $dst $msgType $direction", $sec, $usec);

        if ($this->isRequest()) {
            $this->printRequest($sec, $usec);
        } else {
            $this->printResponse($sec, $usec);
        }
        echo "\n";
    }

    public function isRequest()
    {
        if ($this->msgType === static::MSG_MULTI) {
            if (is_array($this->payload) && $this->payload) {
                /** @var static $cmdMsg */
                $cmdMsg = $this->payload[0];
                if ($cmdMsg->msgType === static::MSG_BULK) {
                    $cmd = $cmdMsg->payload;
                    return static::isRedisCmd($cmd);
                }
            }
        }

        return false;
    }

    public static function dissectPayload(RedisPDU $msg)
    {
        if(strpos($msg->payload, "a:") === 0){
            $value = unserialize($msg->payload);
            $value = json_encode($value);
        } else {
            $value = $msg->payload;
            if (static::isGzip($value)) {
                $value = gzdecode($value);
            }
        }

        return $value;
        // return json_encode($value);
    }

    public function getArgs()
    {
        // assert($this->isRequest());
        /** @var static $item */
        $args = [];
        foreach ($this->payload as $item) {
            assert($item->msgType === static::MSG_BULK);
            $args[] = $item->payload;
        }

        foreach ($args as &$arg) {
            if (static::isGzip($arg)) {
                $arg = gzdecode($arg);
            };
        }
        unset($arg);

        return $args;
    }

    private function printRequest($sec, $usec)
    {
        /** @var static $item */
        $args = $this->getArgs();
        $cmd = array_shift($args);
        $args = T::format(implode(" ", $args), T::DIM);
        sys_echo(T::format($cmd, T::FG_GREEN) . " " . $args, $sec, $usec);
    }

    private function printResponse($sec, $usec)
    {
        $this->printRecursive($this);
    }

    public static function isGzip($bin)
    {
        if (strlen($bin) <= 3) {
            return false;
        } else {
            return substr($bin, 0, 3) === "\x1f\x8b\x08";
        }
    }

    public function printRecursive(RedisPDU $msg, $seq = 1, $level = -1)
    {
        if ($level <= 0) {
            $paddingLeft = "";
        } else {
            $paddingLeft = str_repeat("  ", $level);
        }

        $seq = $paddingLeft . "$seq) ";

        switch ($msg->msgType) {
            case static::MSG_MULTI:
                if (is_array($this->payload)) {
                    foreach ($this->payload as $seq => $subMsg) {
                        $this->printRecursive($subMsg, $seq + 1, $level + 1);
                    }
                } else {
                    echo $seq, T::format(static::dissectPayload($msg), T::DIM), "\n";
                }
                break;

            case static::MSG_BULK:
                echo $seq, T::format(static::dissectPayload($msg), T::DIM), "\n";
                break;

            case static::MSG_STATUS:
                echo $seq, T::format(static::dissectPayload($msg), T::DIM), "\n";
                break;

            case static::MSG_ERROR:
                echo $seq, T::format(static::dissectPayload($msg), T::DIM), "\n";
                break;

            case static::MSG_INTEGER:
                echo $seq, T::format(static::dissectPayload($msg), T::DIM), "\n";
                break;

            default:
                print_r($this);
                sys_error("invalid redis pdu");
        }
    }
}