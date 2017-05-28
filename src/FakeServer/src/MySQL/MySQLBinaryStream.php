<?php

namespace Minimalism\FakeServer\MySQL;


use Minimalism\FakeServer\Buffer\BinaryStream;


/**
 * Class MySQLBinaryStream
 * @package Minimalism\FakeServer\MySQL
 *
 * https://dev.mysql.com/doc/dev/mysql-server/latest/PAGE_PROTOCOL.html
 * https://dev.mysql.com/doc/internals/en/client-server-protocol.html
 * http://hutaow.com/blog/2013/11/06/mysql-protocol-analysis/
 */
class MySQLBinaryStream extends BinaryStream
{
    const BANNER = "Fake Mysql Server 1.0";
    const CHARSET = 0x21; /* utf8 COLLATE utf8_general_ci (33) */
    const MAX_PACKET_LEN = 0xFFFFFF;
    const MYSQL_ERRMSG_SIZE = 512;
    const DEFAULT_CATEGORY = "def";

    /*
     * Server Status: 0x0002
     * .... .... .... ...0 = In transaction: Not set
     * .... .... .... ..1. = AUTO_COMMIT: Set
     * .... .... .... .0.. = More results: Not set
     * .... .... .... 0... = Multi query - more resultsets: Not set
     * .... .... ...0 .... = Bad index used: Not set
     * .... .... ..0. .... = No index used: Not set
     * .... .... .0.. .... = Cursor exists: Not set
     * .... .... 0... .... = Last row sent: Not set
     * .... ...0 .... .... = database dropped: Not set
     * .... ..0. .... .... = No backslash escapes: Not set
     * .... .0.. .... .... = Session state changed: Not set
     * .... 0... .... .... = Query was slow: Not set
     * ...0 .... .... .... = PS Out Params: Not set
     */
    const SERVER_STATUS_AUTOCOMMIT = 0x0002;

    /**
     * Server Capabilities: 0xffff
     * .... .... .... ...1 = Long Password: Set
     * .... .... .... ..1. = Found Rows: Set
     * .... .... .... .1.. = Long Column Flags: Set
     * .... .... .... 1... = Connect With Database: Set
     * .... .... ...1 .... = Don't Allow database.table.column: Set
     * .... .... ..1. .... = Can use compression protocol: Set
     * .... .... .1.. .... = ODBC Client: Set
     * .... .... 1... .... = Can Use LOAD DATA LOCAL: Set
     * .... ...1 .... .... = Ignore Spaces before '(': Set
     * .... ..1. .... .... = Speaks 4.1 protocol (new flag): Set
     * .... .1.. .... .... = Interactive Client: Set
     * .... 1... .... .... = Switch to SSL after handshake: Set
     * ...1 .... .... .... = Ignore sigpipes: Set
     * ..1. .... .... .... = Knows about transactions: Set
     * .1.. .... .... .... = Speaks 4.1 protocol (old flag): Set
     * 1... .... .... .... = Can do 4.1 authentication: Set
     */
    const SERVER_CAPABILITIES = 0xffff;

    /**
     * Extended Server Capabilities: 0xc1ff
     * .... .... .... ...1 = Multiple statements: Set
     * .... .... .... ..1. = Multiple results: Set
     * .... .... .... .1.. = PS Multiple results: Set
     * .... .... .... 1... = Plugin Auth: Set
     * .... .... ...1 .... = Connect attrs: Set
     * .... .... ..1. .... = Plugin Auth LENENC Client Data: Set
     * .... .... .1.. .... = Client can handle expired passwords: Set
     * .... .... 1... .... = Session variable tracking: Set
     * .... ...1 .... .... = Deprecate EOF: Set
     * 1100 000. .... .... = Unused: 0x60
     */
    const SERVER_CAPABILITIES_EX = 0xc1ff;

    /**
     * Client Capabilities: 0x8208
     * .... .... .... ...0 = Long Password: Not set
     * .... .... .... ..0. = Found Rows: Not set
     * .... .... .... .0.. = Long Column Flags: Not set
     * .... .... .... 1... = Connect With Database: Set
     * .... .... ...0 .... = Don't Allow database.table.column: Not set
     * .... .... ..0. .... = Can use compression protocol: Not set
     * .... .... .0.. .... = ODBC Client: Not set
     * .... .... 0... .... = Can Use LOAD DATA LOCAL: Not set
     * .... ...0 .... .... = Ignore Spaces before '(': Not set
     * .... ..1. .... .... = Speaks 4.1 protocol (new flag): Set
     * .... .0.. .... .... = Interactive Client: Not set
     * .... 0... .... .... = Switch to SSL after handshake: Not set
     * ...0 .... .... .... = Ignore sigpipes: Not set
     * ..0. .... .... .... = Knows about transactions: Not set
     * .0.. .... .... .... = Speaks 4.1 protocol (old flag): Not set
     * 1... .... .... .... = Can do 4.1 authentication: Set
     */

    /**
     * Extended Client Capabilities: 0x0008
     * .... .... .... ...0 = Multiple statements: Not set
     * .... .... .... ..0. = Multiple results: Not set
     * .... .... .... .0.. = PS Multiple results: Not set
     * .... .... .... 1... = Plugin Auth: Set
     * .... .... ...0 .... = Connect attrs: Not set
     * .... .... ..0. .... = Plugin Auth LENENC Client Data: Not set
     * .... .... .0.. .... = Client can handle expired passwords: Not set
     * .... .... 0... .... = Session variable tracking: Not set
     * .... ...0 .... .... = Deprecate EOF: Not set
     * 0000 000. .... .... = Unused: 0x00
     */

    // An OK packet is sent from the server to the client to signal successful completion of a command.
    // As of MySQL 5.7.5, OK packes are also used to indicate EOF, and EOF packets are deprecated.

    // These rules distinguish whether the packet represents OK or EOF:
    //  OK: header = 0 and length of packet > 7
    //  EOF: header = 0xfe and length of packet < 9

    // EOF 0xFE
    // Warning
    // The EOF_Packet packet may appear in places where a Protocol::LengthEncodedInteger may appear.
    // You must check whether the packet length is less than 9 to make sure that it is a EOF_Packet packet.
    /**
    响应报文类型	第1个字节取值范围
    OK 响应报文	0x00
    Error 响应报文	0xFF
    Result Set 报文	0x01 - 0xFA
    Field 报文	0x01 - 0xFA
    Row Data 报文	0x01 - 0xFA
    EOF 报文	0xFE
     */

    public function write3ByteIntLE($i)
    {
        return $this->buffer->write(substr(pack('V', $i), 0, 3));
    }

    public function read3ByteIntLE()
    {
        return unpack("V", $this->buffer->read(3) . "\0\0")[1];
    }

    /**
     * @param int $packetNum
     * @return bool
     *
     * header: 3bytes len + 1byte packetNum
     * body: n bytes
     *
     * len: 用于标记当前请求消息的实际数据长度值，以字节为单位，占用3个字节，最大值为 0xFFFFFF，即接近 16 MB 大小（比16MB少1个字节）。
     * seq: 在一次完整的请求/响应交互过程中，用于保证消息顺序的正确，每次客户端发起请求时，序号值都会从0开始计算。
     * body: 消息体用于存放请求的内容及响应的数据，长度由消息头中的长度值决定。
     */
    public function prependHeader($packetNum)
    {
        return $this->prepend(substr(pack('V', $this->readableBytes()), 0, 3) . pack('C', $packetNum % 256));
    }

    // length coded binary: a variable-length number
    // Length Coded String: a variable-length string.
    // Used instead of Null-Terminated String,
    // especially for character strings which might contain '/0' or might be very long.
    // The first part of a Length Coded String is a Length Coded Binary number (the length);
    // the second part of a Length Coded String is the actual data. An example of a short
    // Length Coded String is these three hexadecimal bytes: 02 61 62, which means "length = 2, contents = 'ab'".

    /**
    Value Of     # Of Bytes  Description
    First Byte   Following
    ----------   ----------- -----------
    0-250        0           = value of first byte
    251          0           column value = NULL
    only appropriate in a Row Data Packet
    252          2           = value of following 16-bit word
    253          3           = value of following 24-bit word
    254          8           = value of following 64-bit word
     */

    // One may ask why the 1 byte length is limited to 251, when the first reserved value in the
    // net_store_length( ) is 252. The code 251 has a special meaning. It indicates that there is
    // no length value or data following the code, and the value of the field is the SQL

    public function writeLengthCodedBinary($length)
    {
        // 251 null
        if ($length === 0) {
            $this->writeUInt8(0x00);
        } else if ($length < 251) {
            $this->writeUInt8($length);
        } else if ($length < 0x10000) {
            $this->writeUInt8(252);
            $this->writeUInt16LE($length);
        } else if ($length < 0x1000000) {
            $this->writeUInt8(253);
             $this->write3ByteIntLE($length);
//            $this->writeUInt32LE($length);
        } else {
            $this->writeUInt8(254);
            $this->writeUInt64LE($length);
        }
    }

    public function getLengthCodedBinaryLen()
    {
        $prefix = unpack("C", $this->buffer->get(1))[1];
        switch ($prefix) {
            case 251:
                return 1;
            case 252:
                return 3;
            case 253:
                return 4; // 5?
            case 254:
                return 9;
            default: // < 251
                return 1;
        }
    }

    public function readLengthCodedBinary()
    {
        $prefix = $this->readUInt8();
        switch ($prefix) {
            case 251:
                return 0;
            case 252:
                return $this->readUInt16LE();
            case 253:
                return $this->read3ByteIntLE();
//                return $this->readUInt32LE();
            case 254:
                return $this->readUInt64LE();
            default: // < 251
                return $prefix;
        }
    }

    public function writeLengthCodedString($string)
    {
        if (empty($string)) {
            $this->writeUInt8(0);
        } else {
            $len = strlen($string);
            $this->writeLengthCodedBinary($len);
            $this->write($string);

//            $this->writeUInt8(253);
//            $this->write3ByteIntLE(strlen($string));
//            $this->write($string);
        }
    }

    public function readLengthCodedString()
    {
        $len = $this->readLengthCodedBinary();
        return $this->read($len);


        $c = $this->readUInt8();
        if ($c === 0) {
            return "";
        } else if ($c === 253) {
            $strlen = $this->read3ByteIntLE();
            return $this->read($strlen);
        } else {
            return $this->read($c);
        }
    }

    public function readLengthCodedStringReturnLen(&$return)
    {
        $ll = $this->getLengthCodedBinaryLen();
        $binLen = $this->readLengthCodedBinary();
        $return = $this->read($binLen);
        return $ll + $binLen;

// TODO
        $c = $this->readUInt8();
        if ($c === 0) {
            $return = "";
            return 1;
        } else if ($c === 253) {
            $strlen = $this->read3ByteIntLE();
            $return = $this->read($strlen);
            return 1 + 3 + $strlen;
        } else {
            $return = $this->read($c);
            return 1 + $c;
        }
    }

    public function writeNullTerminatedString($string)
    {
        $string = str_replace("\0", "0", $string);
        $this->write($string);
        $this->write("\0");
    }

    public function readNullTerminatedString()
    {
        $str = "";
        while ($this->readableBytes() > 0) {
            $char = $this->read(1);
            if ($char === "\0") {
                return $str;
            } else {
                $str .= $char;
            }
        }
        return $str;
    }

    public function readUInt8LenString()
    {
        return $this->read($this->readUInt8());
    }

    /**
     * @param $consumeHeader
     * @return int 返回0 数据不足
     * 返回0 数据不足
     * 返回-1 数据过长
     * 返回整数，packet长
     */
    public function isReceiveCompleted($consumeHeader = false)
    {
        if ($this->readableBytes() < 4) {
            return 0;
        } else {
            $bin = $this->get(4);
            $packetNum = unpack("C", substr($bin, 3, 1))[1];
            $len = unpack("V", substr($bin, 0, 3) . "\0\0")[1];

            if ($this->buffer->readableBytes() >= $len + 4) {
                if ($consumeHeader) {
                    $this->buffer->read(4);
                }
                return $len;
            } else {
                return 0;
            }
        }
    }

    /**
     * @param string $salt 用于客户端加密密码
     */
    public function writeGreetingPacket($salt)
    {
        $this->writeUInt8(0x0A); // Protocol
        $this->writeNullTerminatedString(static::BANNER); // Banner
        $this->writeUInt32LE(getmypid()); // Thread ID
        $this->writeNullTerminatedString($salt); // Salt
        $this->writeUInt16LE(static::SERVER_CAPABILITIES); // Server Capabilities
        $this->writeUInt8(static::CHARSET); // Server Language: utf8 COLLATE utf8_general_ci (33)
        $this->writeUInt16LE(static::SERVER_STATUS_AUTOCOMMIT); // Server Status
        $this->writeUInt16LE(static::SERVER_CAPABILITIES_EX); // Extended Server Capabilities
        $this->writeUInt8(21); // Authentication Plugin Length: 21
        $this->write(str_repeat("\0", 10)); // Unused: 00000000000000000000
        $this->writeNullTerminatedString("$salt"); // salt
        $this->write("mysql_native_password\0"); // Authentication Plugin: mysql_native_password
    }

    public function readGreetingPacket()
    {

    }

    public function readAuthorizationPacket()
    {
        $capabilities = $this->readUInt16LE(); // Client Capabilities
        $exCapabilities = $this->readUInt16LE(); // Extended Client Capabilities
        $maxPacket = $this->readUInt32LE(); // MAX Packet: 300
        $charset = $this->readUInt8(); // Charset: utf8 COLLATE utf8_general_ci (33)
        $unused = $this->read(23); // 23 Bytes 0x00
        $username = $this->readNullTerminatedString(); // Username: root
        $password = $this->readUInt8LenString(); // Password: 71f31c52cab00272caa32423f1714464113b7819
        $schema = $this->readNullTerminatedString(); // Schema: test
        $clientAuthPlugin = $this->readNullTerminatedString(); // Client Auth Plugin: mysql_native_password

        $r = get_defined_vars();
        unset($r["loginRequest"]);
        return $r;
    }

    /**
     * 客户端的命令执行正确时，服务器会返回OK响应报文。
     *
     * @param string $message
     * @param int $affectedRows
     * @param int $lastInsertId
     * @param int $warningCount
     *
     * 字节	说明
     * 1	OK报文，值恒为0x00
     * 1-9	受影响行数（Length Coded Binary） 当执行INSERT/UPDATE/DELETE语句时所影响的数据行数。
     * 1-9	索引ID值（Length Coded Binary） 索引ID值：该值为AUTO_INCREMENT索引字段生成，如果没有索引字段，则为0x00。注意：当INSERT插入语句为多行数据时，该索引ID值为第一个插入的数据行索引值，而非最后一个。
     * 2	服务器状态 客户端可以通过该值检查命令是否在事务处理中。
     * 2	告警计数 告警发生的次数。
     * n	服务器消息（字符串到达消息尾部时结束，无结束符，可选） 服务器返回给客户端的消息，一般为简单的描述性字符串，可选字段。
     */
    public function writeResponseOK($message = "", $affectedRows = 0, $lastInsertId = 0, $warningCount = 0)
    {
        assert(strlen($message) <= self::MYSQL_ERRMSG_SIZE);

        $this->writeUInt8(0x00); // OK always = 0x00
        $this->writeLengthCodedBinary($affectedRows);
        $this->writeLengthCodedBinary($lastInsertId);
        $this->writeUInt16LE(self::SERVER_STATUS_AUTOCOMMIT);
        $this->writeUInt16LE($warningCount);
        $this->writeLengthCodedString($message);
    }

    public function readResponseOK($len)
    {

    }

    /**
     * @param string $message
     * @param int $errno
     * @param string $sqlstate
     *
     * 字节	说明
     * 1	Error报文，值恒为0xFF
     * 2	错误编号（小字节序） 错误编号值定义在源代码/include/mysqld_error.h头文件中。
     * 1	服务器状态标志，恒为'#'字符
     * 5	服务器状态（5个字符）服务器将错误编号通过mysql_errno_to_sqlstate函数转换为状态值，状态值由5字节的ASCII字符组成，定义在源代码/include/sql_state.h头文件中。
     * n	服务器消息 错误消息字符串到达消息尾时结束，长度可以由消息头中的长度值计算得出。消息长度为0-512字节。
     */
    public function writeResponseERR($message = "Unknown MySQL error", $errno = 2000, $sqlstate = "HY000")
    {
        assert(strlen($message) <= self::MYSQL_ERRMSG_SIZE);

        $this->writeUInt8(0xff);    // ERR field_count, always = 0xff
        $this->writeUInt16LE($errno);
        assert(strlen($sqlstate) === 5);
        $sqlstate = substr($sqlstate, 0, 5);
        $this->write("#$sqlstate"); // sqlstate marker, always #
        $this->writeNullTerminatedString("$message");
    }

    public function readResponseERR()
    {

    }

    /**
     * @param int $fieldCount
     * @param null $ext
     *
     * 字节	说明
     * 1-9	Field结构计数（Length Coded Binary） 用于标识Field结构的数量，取值范围0x00-0xFA。
     * 1-9	额外信息（Length Coded Binary）可选字段，一般情况下不应该出现。只有像SHOW COLUMNS这种语句的执行结果才会用到额外信息（标识表格的列数量）。
     */
    public function writeResultSetHeader($fieldCount, $ext = null)
    {
        assert($fieldCount >= 0x00 && $fieldCount <= 0xFA);
        $this->writeLengthCodedBinary($fieldCount);
        if ($ext !== null) {
            $this->writeLengthCodedBinary($ext);
        }
    }

    public function readResultSetHeader($packetLen)
    {
        $left = $packetLen - $this->getLengthCodedBinaryLen();
        $fieldCount = $this->readLengthCodedBinary();
        if ($left > 0) {
            $ext = $this->readLengthCodedBinary();
        } else {
            $ext = null;
        }

        return [ $fieldCount, $ext ];
    }

    /**
     *
     * 字节    说明
     * n    目录名称（Length Coded String） 在4.1及之后的版本中，该字段值为"def"。
     * n    数据库名称（Length Coded String） 数据库名称标识。
     * n    数据表名称（Length Coded String） 数据表的别名（AS之后的名称）。
     * n    数据表原始名称（Length Coded String） 数据表的原始名称（AS之前的名称）。
     * n    列（字段）名称（Length Coded String） 列（字段）的别名（AS之后的名称）。
     * n    列（字段）原始名称（Length Coded String） 列（字段）的原始名称（AS之前的名称）。
     * 1    填充值
     * 2    字符编码 列（字段）的字符编码值。
     * 4    列（字段）长度 列（字段）的长度值，真实长度可能小于该值，例如VARCHAR(2)类型的字段实际只能存储1个字符。
     * 1    列（字段）类型 列（字段）的类型值
     * 2    列（字段）标志
     * 1    整型值精度 该字段对DECIMAL和NUMERIC类型的数值字段有效，用于标识数值的精度（小数点位置）。
     * 2    填充值（0x00） 该字段用在数据表定义中，普通的查询结果中不会出现。
     * n    默认值（Length Coded String）
     *
     * 附：Field结构的相关处理函数：
     * 客户端：/client/client.c源文件中的unpack_fields函数
     * 服务器：/sql/sql_base.cc源文件中的send_fields函数
     * @param MySQLField $field
     */
    public function writeField(MySQLField $field)
    {
        $this->writeLengthCodedString($field->catalog);
        $this->writeLengthCodedString($field->database);
        $this->writeLengthCodedString($field->table);
        $this->writeLengthCodedString($field->originalTable);
        $this->writeLengthCodedString($field->name);
        $this->writeLengthCodedString($field->originalName);
        $this->writeUInt8(0);
        $this->writeUInt16LE($field->charset);
        $this->writeUInt32LE($field->length);
        $this->writeUInt8($field->type);
        $this->writeUInt16LE($field->flags);
        $this->writeUInt8($field->decimals);
        $this->writeUInt16LE(0); // Reserved.
        if ($field->default !== null) {
            $this->writeLengthCodedString($field->default);
        }
    }

    public function readField($len)
    {
        $field = new MySQLField();

        $len -= $this->readLengthCodedStringReturnLen($field->catalog);
        $len -= $this->readLengthCodedStringReturnLen($field->database);
        $len -= $this->readLengthCodedStringReturnLen($field->table);
        $len -= $this->readLengthCodedStringReturnLen($field->originalTable);
        $len -= $this->readLengthCodedStringReturnLen($field->name);
        $len -= $this->readLengthCodedStringReturnLen($field->originalName);
        $filter1 = $this->readUInt8(); $len -= 1;
        $field->charset = $this->readUInt16LE(); $len -= 2;
        $field->length = $this->readUInt32LE(); $len -= 4;
        $field->type = $this->readUInt8(); $len -= 1;
        $field->flags = $this->readUInt16LE(); $len -= 2;
        $field->decimals = $this->readUInt8(); $len -= 1;
        $filter2 = $this->readUInt16LE(); $len -= 2;
        if ($len > 0) {
            $field->default = $this->readLengthCodedString();
        }

        return $field;
    }

    /**
     * EOF结构用于标识Field和Row Data的结束，在预处理语句中，EOF也被用来标识参数的结束。
     *
     * @param int $warningCount
     * @param $flags
     *
     * 字节	说明
     * 1	EOF值（0xFE）
     * 2	告警计数 服务器告警数量，在所有数据都发送给客户端后该值才有效。
     * 2	状态标志位 包含类似SERVER_MORE_RESULTS_EXISTS这样的标志位。
     *
     * 注：由于EOF值与其它Result Set结构共用1字节，所以在收到报文后需要对EOF包的真实性进行校验，校验条件为：
     * 第1字节值为0xFE
     * 包长度小于9字节
     *
     * 附：EOF结构的相关处理函数：
     * 服务器：protocol.cc源文件中的send_eof函数
     *
     * • End-of-field information data in a result set
     * • End-of-row data in a result set
     * • Server acknowledgment of COM_SHUTDOWN
     * • Server reporting success in response to COM_SET_OPTION and COM_DEBUG
     * • Request for the old-style credentials during authenticat
     */
    public function writeEOF($warningCount = 0, $flags = 0)
    {
        $this->writeUInt8(0xFE);
        // 4.1 之前没有以下字段
        $this->writeUInt16LE($warningCount);
        $this->writeUInt16LE($flags);
    }

    public function readEOF()
    {
        assert($this->readUInt8() === 0XFE);
        $serverStatus = $this->readUInt16LE();
        $flags = $this->readUInt16LE();
        return [$serverStatus, $flags];
    }


    /**
     * Following the field definition sequence of packets, the server sends the actual rows of
    data, one packet per row. Each row data packet consists of a sequence of values
    stored in the standard field data format. When reporting the result of a regular query
    (sent with COM_QUERY), the field data is converted to the string format. When using a
    prepared statement (COM_PREPARE), the field data is sent in its native format with the
    low byte first
     */

    /**
     * 在Result Set消息中，会包含多个Row Data结构，每个Row Data结构又包含多个字段值，这些字段值组成一行数据。
     *
     * @param MySQLField[] $fields
     * @param array $rowValue
     *
     * 字节    说明
     * n    字段值（Length Coded String） 行数据中的字段值，字符串形式。
     * ...    （一行数据中包含多个字段值）
     *
     * 附：Row Data结构的相关处理函数：
     * 客户端：/client/client.c源文件中的read_rows函数
     */
    public function writeRowData(array $fields, array $rowValue)
    {
        foreach ($fields as $field) {
            // NOTICE!!!
            if (isset($rowValue[$field->name])) {
                $value = $rowValue[$field->name];
            } else {
                $value = $field->default;
            }
            $this->writeLengthCodedString(strval($value));
        }
    }

    public function readRowData($len)
    {
        $row = [];
        while ($len > 0) {
            $len -= $this->readLengthCodedStringReturnLen($field);
            $row[] = $field;
            unset($field);
        }

        return $row;
    }

    /**
     * 该结构用于传输二进制的字段值，既可以是服务器返回的结果，也可以是由客户端发送的
     * （当执行预处理语句时，客户端使用Result Set消息来发送参数及数据）。
     *
     * @param MySQLField[] $fields
     * @param array $rowValue
     *
     * 字节    说明
     * 1    结构头（0x00） 前2个比特位被保留，值分别为0和1，以保证不会和OK、Error包的首字节冲突。在MySQL 5.0及之后的版本中，这2个比特位的值都为0。
     * (列数量 + 7 + 2) / 8    空位图
     * n    字段值 行数据中的字段值，二进制形式。
     * ...    （一行数据中包含多个字段值）
     */
    public function writeRowDataBin(array $fields, array $rowValue)
    {
        $this->writeUInt8(0);
        $this->write(str_repeat("\0", intval((count($fields) + 7 + 2) / 8)));
        foreach ($fields as $field) {
            // NOTICE!!!
            if (isset($rowValue[$field->name])) {
                $value = $rowValue[$field->name];
            } else {
                $value = $field->default;
            }
            $this->writeLengthCodedBinary(strval($value));
        }
    }

    public function readRowDataBin()
    {

    }

    /**
     *
     * @param int $stmtId
     * @param MySQLField[] $fields
     * @param MySQLField[] $parameters
     * @param int $warningCount
     *
     * 其中 PREPARD_OK 的结构如下：
     *
     * 字节    说明
     * 1    OK报文，值为0x00
     * 4    预处理语句ID值
     * 2    列数量
     * 2    参数数量
     * 1    填充值（0x00）
     * 2    告警计数
     */
    public function writePrepareOK($stmtId, array $fields, array $parameters, $warningCount = 0)
    {
        $this->writeUInt8(0x00);
        $this->writeInt32LE($stmtId);
        $this->writeUInt16LE(count($fields));
        $this->writeUInt16LE(count($parameters));
        $this->writeUInt8(0);
        $this->writeUInt16LE($warningCount);
    }

    public function readPrepareOK()
    {

    }

    /**
     * 预处理语句的值与参数正确对应后，服务器会返回 Parameter 报文。
     *
     * @param MySQLField $parameter
     *
     * 字节    说明
     * 2    类型 与 Field 结构中的字段类型相同。
     * 2    标志 与 Field 结构中的字段标志相同。
     * 1    数值精度 与 Field 结构中的数值精度相同。
     * 4    字段长度 与 Field 结构中的字段长度相同。
     */
    public function writeParameter(MySQLField $parameter)
    {
        $this->writeUInt16LE($parameter->type); // TODO 这里为啥用两个字节表示, field中用1个字节表示
        $this->writeUInt16LE($parameter->flags);
        $this->writeUInt8($parameter->decimals);
        $this->writeUInt32LE($parameter->length);
    }

    public function readParameter()
    {

    }

    public function writeCommand()
    {

    }

    public function readCommand()
    {
        $cmd = $this->readUInt8();

        switch ($cmd) {
            case MySQLCommand::COM_SLEEP:
                break;

            case MySQLCommand::COM_QUIT:
                // no args
                break;

            case MySQLCommand::COM_QUERY:
                $sql = $this->read($this->readableBytes());
                break;

            case MySQLCommand::COM_FIELD_LIST:
                $table = $this->readNullTerminatedString();
                $column = $this->read($this->readableBytes());

                break;

            case MySQLCommand::COM_INIT_DB:
            case MySQLCommand::COM_CREATE_DB:
            case MySQLCommand::COM_DROP_DB:
                $database = $this->read($this->readableBytes());

                break;
            case MySQLCommand::COM_REFRESH:
                $flag = $this->readUInt8();

                break;
            case MySQLCommand::COM_SHUTDOWN:
                $flag = $this->readUInt8();

                break;
            case MySQLCommand::COM_STATISTICS:
                // no args
                break;

            case MySQLCommand::COM_PROCESS_INFO:
                // no args
                break;

            case MySQLCommand::COM_CONNECT:
                break;

            case MySQLCommand::COM_PROCESS_KILL:
                $connId = $this->readUInt32LE();

                break;

            case MySQLCommand::COM_DEBUG:
                // no args
                break;

            case MySQLCommand::COM_PING:
                // no args
                break;

            case MySQLCommand::COM_TIME:
                break;

            case MySQLCommand::COM_DELAYED_INSERT:
                break;

            case MySQLCommand::COM_CHANGE_USER:
                $username = $this->readNullTerminatedString();
                $password = $this->readLengthCodedString();
                $database = $this->readNullTerminatedString();
                $charset  = $this->readUInt16LE();
                break;

            case MySQLCommand::COM_BINLOG_DUMP:
                $offset = $this->readUInt32LE(); // 二进制日志数据的起始位置（小字节序）
                $flag = $this->readUInt32LE(); // 二进制日志数据标志位（目前未使用，永远为0x00）
                $slaveId = $this->readUInt32LE(); // 从服务器的服务器ID值（小字节序）
                $fileName = $this->read($this->readableBytes()); // 二进制日志的文件名称（可选，默认值为主服务器上第一个有效的文件名）
                break;


            case MySQLCommand::COM_TABLE_DUMP:
                $database = $this->readLengthCodedString();
                $table = $this->readLengthCodedString();

                break;


            case MySQLCommand::COM_CONNECT_OUT:
                break;

            case MySQLCommand::COM_REGISTER_SLAVE:
                $slaveId = $this->readUInt32LE(); // 从服务器ID值（小字节序）
                $masterIP = $this->readLengthCodedString(); // 主服务器IP地址（Length Coded String）
                $masterUsername = $this->readLengthCodedString(); // 主服务器用户名（Length Coded String）
                $masterPassword = $this->readLengthCodedString(); // 主服务器密码（Length Coded String）
                $masterPort = $this->readUInt16LE(); // 主服务器端口号
                $level = $this->readUInt32LE(); // 安全备份级别（由MySQL服务器rpl_recovery_rank变量设置，暂时未使用）
                $masterId = $this->readUInt32LE(); // 主服务器ID值（值恒为0x00）
                break;

            case MySQLCommand::COM_STMT_PREPARE:
                $sql = $this->read($this->readableBytes());
                break;

            case MySQLCommand::COM_STMT_EXECUTE:
                $stmtId = $this->readUInt32LE();
                $flag = $this->readUInt8();
                $unused = $this->readUInt32LE();
                // TODO
                /**
                如果参数数量大于0
                n	空位图（Null-Bitmap，长度 = (参数数量 + 7) / 8 字节）
                1	参数分隔标志
                如果参数分隔标志值为1
                n	每个参数的类型值（长度 = 参数数量 * 2 字节）
                n	每个参数的值
                 */
                break;

            case MySQLCommand::COM_STMT_SEND_LONG_DATA:
                $stmtId = $this->readUInt32LE();
                $parameterId = $this->readUInt16LE();
                $payload = $this->read($this->readableBytes());
                $type = unpack("v", substr($payload, 0, 2))[1];
                if (false) {
                    // TODO $type 是否是有效类型
                } else {
                    // $type
                    $payload = substr($payload, 2);
                }

                break;

            case MySQLCommand::COM_STMT_CLOSE:
                $stmtId = $this->readUInt32LE();
                break;

            case MySQLCommand::COM_STMT_RESET:
                $stmtId = $this->readUInt32LE();
                break;

            case MySQLCommand::COM_SET_OPTION:
                $flag = $this->readUInt16LE();
                break;

            case MySQLCommand::COM_STMT_FETCH:
                $stmtId = $this->readUInt32LE();
                $rows = $this->readUInt32LE();
                break;

            default:

        }

        $args = get_defined_vars();
        unset($args["cmd"]);

        return [$cmd, $args];
    }
}