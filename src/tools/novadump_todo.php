<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/4/10
 * Time: 下午4:08
 */


//$buf = new MemoryBuffer();
//$buf->write("123\r\n232\r\n123");
//while(($line = $buf->readLine()) !== false) {
//    var_dump($line);
//};
//exit;
//
//function mysql_dissector()
//{
//    $mbuf = new MemoryBuffer();
//
//}
//
//function is_redis_proto()
//{
//}
//
//interface PacketDissector
//{
//    public function dissector(MemoryBuffer $mbuf);
//}
//
//class RedisDissector implements PacketDissector
//{
//    const CRLF = "\r\n";
//
//    private static $mTypes = [
//        '+' => 'Status',
//        '-' => 'Error',
//        ':' => 'Integer',
//        '$' => 'Bulk',
//        '*' => 'Multi-Bulk',
//    ];
//
//    public function isRedisProto(MemoryBuffer $mbuf)
//    {
//        $firstChar = $mbuf->get(1);
//        return isset(static::$mTypes[$firstChar]);
//    }
//
//    public function dissector(MemoryBuffer $mBuf)
//    {
//        if (static::isRedisProto($mBuf) === false) {
//            return false;
//        }
//
//
//    }
//
//    private function readLine(MemoryBuffer $mBuf)
//    {
//        $line = $mBuf->readLine();
//        if (substr($line, -2) !== "\r\n") {
//            $mBuf->write($line);
//            return false;
//        }
//
//        $line = substr($line, 0, -2);
//        return $line;
//    }
//
//
//    private function parse(MemoryBuffer $mBuf)
//    {
//        $line = $this->readLine($mBuf);
//        if ($line === false) {
//            assert(false);
//        }
//
//        $r = preg_match('#([-+:$*])(.+)#', $line, $matches);
//        if ($r !== 1) {
//            assert(false);
//        }
//        list(, $prefix, $text) = $matches;
//        if (!isset(static::$mTypes[$prefix])) {
//            assert(false);
//        }
//
//
//        $mType = static::$mTypes[$prefix];
//
//        switch ($prefix) {
//            case '*':
//                $r = [];
//                $replies = intval($text);
//                while ($replies) {
//                    $r[] = $this->parse($mBuf);
//                    $replies--;
//                }
//
//                return [$mType, $r];
//
//            case '+':
//                break;
//            case '-':
//                break;
//            case ':':
//                break;
//            case '$':
//                break;
//        }
//    }
//}
//
//
//exit;
//function redis_dissector(MemoryBuffer $mbuf)
//{
//
//}
