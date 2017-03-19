<?php

namespace Minimalism\Socket;

/**
 * Socket
 *
 * @method static bool create_pair ( int $domain , int $type , int $protocol , array &$fd) — Creates a pair of indistinguishable sockets and stores them in an array
 *
 * @method static resource|false create_listen ( int $port , int $backlog = 128) — Opens a socket on port to accept connections
 * @method static resource|false create ( int $domain , int $type , int $protocol) — Create a socket (endpoint for communication)
 *
 * @method static bool bind ( resource $socket , string $address, int $port = 0) — Binds a name to a socket
 * @method static bool listen ( resource $socket, int $backlog = 0) — Listens for a connection on a socket
 * @method static resource|false accept ( resource $socket ) — Accepts a connection on a socket

 * @method static bool connect ( resource $socket , string $address, int $port = 0) — Initiates a connection on a socket
 *
 * @method static bool shutdown ( resource $socket, int $how = 2) — Shuts down a socket for receiving, sending, or both
 * @method static void close ( resource $socket ) — Closes a socket resource
 *
 * @method static string|false read ( resource $socket , int $length, int $type = PHP_BINARY_READ) — Reads a maximum of length bytes from a socket
 * @method static int|false write ( resource $socket , string $buffer, int $length = null) — Write to a socket
 *
 * @method static bool set_block ( resource $socket ) — Sets blocking mode on a socket resource
 * @method static bool set_nonblock ( resource $socket ) — Sets nonblocking mode for file descriptor fd
 *
 * @ method static int|false select ( array &$read , array &$write , array &$except , int $tv_sec, int $tv_usec = 0) — Runs the select() system call on the given arrays of sockets with a specified timeout
 *
 * @method static bool set_option ( resource $socket , int $level , int $optname , mixed $optval ) — Sets socket options for the socket
 * @method static bool setopt ( resource $socket , int $level , int $optname , mixed $optval ) — Alias of socket_set_option
 * @method static mixed get_option ( resource $socket , int $level , int $optname ) — Gets socket options for the socket
 * @method static mixed getopt ( resource $socket , int $level , int $optname ) — Alias of socket_get_option
 *
 * @ method static bool getpeername ( resource $socket , string &$address, int &$port = null ) — Queries the remote side of the given socket which may either result in host/port or in a Unix filesystem path, dependent on its type
 * @ method static bool getsockname ( resource $socket , string &$addr, int &$port = null ) — Queries the local side of the given socket which may either result in host/port or in a Unix filesystem path, dependent on its type
 *
 * @method static int|false send ( resource $socket , string $buf , int $len , int $flags ) — Sends data to a connected socket
 * @ method static int|false recv ( resource $socket , string &$buf , int $len , int $flags ) — Receives data from a connected socket
 *
 * @method static int|false sendto ( resource $socket , string $buf , int $len , int $flags , string $addr, int $port = 0 ) — Sends a message to a socket, whether it is connected or not
 * @ method static int|false recvfrom ( resource $socket , string &$buf , int $len , int $flags , string &$name, int &$port = 0 ) — Receives data from a socket whether or not it is connection-oriented
 *
 * @method static int cmsg_space ( int $level , int $type ) — Calculate message buffer size
 * @method static int|false sendmsg ( resource $socket , array $message , int $flags ) — Send a message
 * @method static int|false recvmsg ( resource $socket , string $message, int $flags = null ) — Read a message
 *
 * @ method static int last_error( resource $socket = null) — Returns the last error on the socket
 * @method static string strerror ( int $errno ) — Return a string describing a socket error
 * @method static void clear_error( resource $socket = null) — Clears the error on the socket or the last error code
 *
 * @method static resource|false import_stream ( resource $stream ) — Import a stream
 *
 * 1. 自动检测错误, 抛出异常, so_errno -> ex->getCode
 *      1) callStatic
 *      2) 参数传递引用的单独封装: select, getpeername, getsockname, recv, recvfrom
 * 2. last_error 修改为自动清除 last_error & clear_error
 * 3. sendfd + recvfd
 *
 */
final class Socket
{
    /**
     * @param string $func
     * @param array $args
     * @return bool
     */
    public static function __callStatic($func, array $args)
    {
        $socket_func = "socket_$func";
        $ret = $socket_func(...$args);

        // get_option && getopt 不能以返回值 === false 判断调用失败
        if ($func === "get_option" || $func === "getopt") {
            return $ret;
        }

        if ($func === "set_opt" || $func === "setopt") {
            // socket_set_option 同时可能返回null
            // 比如 当前系统不支持reuse port时候
            // socket_set_option(socket_create(AF_INET, SOCK_STREAM, SOL_TCP), SOL_SOCKET, SO_REUSEPORT, 1) === null
            if ($ret === false || $ret === null) {
                throw self::__toException($func, reset($args));
            }
        }

        if ($ret === false) {
            throw self::__toException($func, reset($args));
        }

        return $ret;
    }

    public static function select(array &$read, array &$write, array &$except, $tv_sec, $tv_usec = 0)
    {
        $n = socket_select($read, $write, $except, $tv_sec, $tv_usec);
        if ($n === false) {
            throw self::__toException("select");
        }
        return $n;
    }

    public static function getpeername($socket, &$address, &$port = null)
    {
        $ok = socket_getpeername($socket, $address, $port);
        if ($ok === false) {
            throw self::__toException("getpeername", $socket);
        }
        return $ok;
    }

    public static function getsockname($socket, &$addr, &$port = null)
    {
        $ok = socket_getsockname($socket, $addr, $port);
        if ($ok === false) {
            throw self::__toException("getsockname", $socket);
        }
        return $ok;
    }

    public static function recv($socket, &$buf, $len, $flags)
    {
        $len = socket_recv($socket, $buf, $len, $flags);
        if ($len === false) {
            throw self::__toException("recv", $socket);
        }
        return $len;
    }

    public static function recvfrom($socket, &$buf, $len, $flags, &$name, &$port = 0)
    {
        $len = socket_recvfrom($socket, $buf, $len, $flags, $name, $port);
        if ($len === false) {
            throw self::__toException("recvfrom", $socket);
        }
        return $len;
    }

    /**
     * socket_last_error & socket_clear_error
     * @param null $socket
     * @return int
     *
     * 参考
     * SO_ERROR vs. errno
     * http://stackoverflow.com/questions/21031717/so-error-vs-errno
     */
    public static function last_error($socket = null)
    {
        if (is_resource($socket)) {
            $so_errno = socket_last_error($socket);
            if ($so_errno === 0) {
                // remove ?!
                $so_errno = socket_get_option($socket, SOL_SOCKET, SO_ERROR); // 同时clear
            } else {
                socket_clear_error($socket);
            }
        } else {
            $so_errno = socket_last_error();
            if ($so_errno !== 0) {
                socket_clear_error();
            }
        }

        return $so_errno;
    }

    /**
     * so_errno to constant name
     * @param $so_errno
     * @return mixed|null
     */
    public static function consterr($so_errno)
    {
        /* @var $socket_errors array so_errno => so_err_str*/
        static $socket_errors = [];

        if ($socket_errors === []) {
            $socket_constants = get_defined_constants(true)["sockets"];
            foreach ($socket_constants as $constant => $value) {
                if ("SOCKET_E" === substr($constant, 0, 8)) {
                    $socket_errors[$value] = $constant;
                }
            }
        }

        return isset($socket_errors[$so_errno]) ? $socket_errors[$so_errno] : null;
    }

    public static function __toException($func, $socket = null)
    {
        $so_errno = Socket::last_error($socket);
        $so_err = Socket::consterr($so_errno);
        $so_errstr = Socket::strerror($so_errno);

        // errno 可能等于 0, 不一定socket_*函数返回false, 就一定有 errno
        $msg = sprintf("call socket_$func fail: %s [errno=%d, err=%s]", $so_errstr, $so_errno, $so_err);

        return new SocketException($msg, $so_errno);
    }

    /**
     * Convert fd to resource
     * @param int $fd
     * @return resource
     */
    public static function fd2Resource($fd)
    {
        assert(is_int($fd));
        $r = fopen("php://fd/$fd", "r+");
        if ($r === false) {
            throw new SocketException("fail to convert fd $fd to resource", 500);
        }
        return $r;
    }

    /**
     * @param $res
     *
     * fd2Resource -> resource2Sock
     * @return resource|void
     *
     * socket_set_option($sock, SOL_SOCKET, SO_SNDTIMEO, [
     * "sec" => 0,
     * "usec" => 100000, // 0.1s
     * ]);
     * socket_set_option($sock, SOL_SOCKET, SO_RCVTIMEO, [
     * "sec" => 0,
     * "usec" => 100000, // 0.1s
     * ]);
     */
    public static function resource2Sock($res)
    {
        assert(is_resource($res));
        $sock = socket_import_stream($res);
        if (!$sock) {
            throw new SocketException("fail to convert resource to socket", 500);
        }
        return $sock;
    }

    /**
     * @param resource $socket
     * @param array $name
     * @param \resource[] ...$sockets
     * @return int
     *
     * fot tcp/ip socket
     * "name"      => ["addr" => "127.0.0.1", "port" => 3000],
     *
     * for unix socket
     * "name" 		=> ["path" => $path],
     *
     * data e.g.
     * "data" 	=> [STDIN, STDOUT, STDERR],
     */
    public static function sendfd($socket, array $name, resource ...$sockets)
    {
        $msgHdr = [
            "name"      => $name,                       // optional address
            "iov" 		=> [],				            // array of I/O buffers 数据报文
            "control" 	=> [[                           // cMsgHdr, anncillary data 这里用来发送fd
                "level" => SOL_SOCKET,
                "type" 	=> SCM_RIGHTS,                  // socket_level control message
                "data" 	=> $sockets,
            ]]
        ];

        return socket_sendmsg($socket, $msgHdr, 0);
    }

    /**
     * @param resource $socket
     * @param array $name
     * @param int $n 接收数量必须与发送数量匹配
     * @return int
     *
     * fot tcp/ip socket
     * "name" => ["family" => AF_INET, "addr" => "127.0.0.1"],
     *
     * for unix socket
     * "name" 			=> [],
     */
    public static function recvfd($socket, array $name = [], $n = 1)
    {
        $msgHdr = [
            "name"          => $name,
            "buffer_size" 	=> 2000,
            "controllen" 	=> socket_cmsg_space(SOL_SOCKET, SCM_RIGHTS, $n)
        ];

        return socket_recvmsg($socket, $msgHdr, 0);
    }
}


/**
 * Class SocketException
 */
class SocketException extends \RuntimeException
{
    public function getConst()
    {
        return Socket::consterr($this->code);
    }
}
