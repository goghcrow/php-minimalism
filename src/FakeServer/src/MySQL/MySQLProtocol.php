<?php

namespace Minimalism\FakeServer\MySQL;

use Minimalism\FakeServer\Buffer\BinaryStream;
use Minimalism\FakeServer\Buffer\Buffer;


class MySQLProtocol
{
    public static function writeLengthCodedBinary(Buffer $buffer, $length)
    {
        $binLen = new BinaryStream($buffer);
        if ($length === 0) {
            $binLen->writeUInt8(251);
        } else if ($length < 251) {
            $binLen->writeUInt8($length);
        } else if ($length < 0x10000) {
            $binLen->writeUInt8(252);
            $binLen->writeUInt16LE($length);
        } else if ($length < 0x1000000) {
            $binLen->writeUInt8(253);
            // writeUInt32LE: 3byte
            $binLen->write(substr(pack('V', $length), 0, 3));
        } else {
            $binLen->writeUInt8(254);
            $binLen->writeUInt64LE($length);
        }
    }

    public static function writeLengthCodedString()
    {

    }

    public static function packResponseOK(Buffer $buffer, $affectedRows = 0, $lastInsertId = 0, $warnings = 0)
    {
        $response = new BinaryStream($buffer);

        // Affected Rows: 0
        self::writeLengthCodedBinary($buffer, $affectedRows);

        // Last Insert Id: 0
        self::writeLengthCodedBinary($buffer, $lastInsertId);

        /*
        Server Status: 0x0002
        .... .... .... ...0 = In transaction: Not set
        .... .... .... ..1. = AUTO_COMMIT: Set
        .... .... .... .0.. = More results: Not set
        .... .... .... 0... = Multi query - more resultsets: Not set
        .... .... ...0 .... = Bad index used: Not set
        .... .... ..0. .... = No index used: Not set
        .... .... .0.. .... = Cursor exists: Not set
        .... .... 0... .... = Last row sent: Not set
        .... ...0 .... .... = database dropped: Not set
        .... ..0. .... .... = No backslash escapes: Not set
        .... .0.. .... .... = Session state changed: Not set
        .... 0... .... .... = Query was slow: Not set
        ...0 .... .... .... = PS Out Params: Not set
         */
        $response->write(0x0002);

        // Warnings: 0
        $response->writeUInt16LE($warnings);

    }

    public static function packResponseKO(Buffer $buffer)
    {

    }

    public static function unpackLoginPacket(Buffer $buffer)
    {
        $loginRequest = new BinaryStream($buffer);

        /**
        Client Capabilities: 0x8208
        .... .... .... ...0 = Long Password: Not set
        .... .... .... ..0. = Found Rows: Not set
        .... .... .... .0.. = Long Column Flags: Not set
        .... .... .... 1... = Connect With Database: Set
        .... .... ...0 .... = Don't Allow database.table.column: Not set
        .... .... ..0. .... = Can use compression protocol: Not set
        .... .... .0.. .... = ODBC Client: Not set
        .... .... 0... .... = Can Use LOAD DATA LOCAL: Not set
        .... ...0 .... .... = Ignore Spaces before '(': Not set
        .... ..1. .... .... = Speaks 4.1 protocol (new flag): Set
        .... .0.. .... .... = Interactive Client: Not set
        .... 0... .... .... = Switch to SSL after handshake: Not set
        ...0 .... .... .... = Ignore sigpipes: Not set
        ..0. .... .... .... = Knows about transactions: Not set
        .0.. .... .... .... = Speaks 4.1 protocol (old flag): Not set
        1... .... .... .... = Can do 4.1 authentication: Set
         */
        $capabilities = $loginRequest->read(2);

        /**
        Extended Client Capabilities: 0x0008
        .... .... .... ...0 = Multiple statements: Not set
        .... .... .... ..0. = Multiple results: Not set
        .... .... .... .0.. = PS Multiple results: Not set
        .... .... .... 1... = Plugin Auth: Set
        .... .... ...0 .... = Connect attrs: Not set
        .... .... ..0. .... = Plugin Auth LENENC Client Data: Not set
        .... .... .0.. .... = Client can handle expired passwords: Not set
        .... .... 0... .... = Session variable tracking: Not set
        .... ...0 .... .... = Deprecate EOF: Not set
        0000 000. .... .... = Unused: 0x00
         */
        $exCapabilities = $loginRequest->read(2);

        // MAX Packet: 300
        $maxPacket = $loginRequest->read(4);

        // Charset: utf8 COLLATE utf8_general_ci (33)
        $charset = $loginRequest->read(1);

        // Username: root
        $username = self::readString($buffer);

        // Password: 71f31c52cab00272caa32423f1714464113b7819
        $password = $loginRequest->read(20);

        // Schema: test
        $schema = self::readString($buffer);

        // Client Auth Plugin: mysql_native_password
        $clientAuthPlugin = self::readString($buffer);

        $vars = get_defined_vars();
        unset($vars["loginRequest"]);
        unset($vars["buffer"]);

        return $vars;
    }

    private static function readString(Buffer $buffer)
    {
        $str = "";
        while ($buffer->readableBytes() > 0) {
            $char = $buffer->read(1);
            if ($char === "\0") {
                return $str;
            } else {
                $str .= $char;
            }
        }
        return $str;
    }

    public static function packGreeting(Buffer $buffer)
    {
        $salt = openssl_random_pseudo_bytes(8);

        $greeting = new BinaryStream($buffer);

        // Protocol
        $greeting->writeUInt8(10);

        // Version
        $greeting->write("Fake Mysql Server 1.0\0");

        // Thread ID
        $greeting->writeUInt32LE(getmypid());

        // Salt
        $greeting->write("$salt\0");

        /**
        Server Capabilities: 0xffff
        .... .... .... ...1 = Long Password: Set
        .... .... .... ..1. = Found Rows: Set
        .... .... .... .1.. = Long Column Flags: Set
        .... .... .... 1... = Connect With Database: Set
        .... .... ...1 .... = Don't Allow database.table.column: Set
        .... .... ..1. .... = Can use compression protocol: Set
        .... .... .1.. .... = ODBC Client: Set
        .... .... 1... .... = Can Use LOAD DATA LOCAL: Set
        .... ...1 .... .... = Ignore Spaces before '(': Set
        .... ..1. .... .... = Speaks 4.1 protocol (new flag): Set
        .... .1.. .... .... = Interactive Client: Set
        .... 1... .... .... = Switch to SSL after handshake: Set
        ...1 .... .... .... = Ignore sigpipes: Set
        ..1. .... .... .... = Knows about transactions: Set
        .1.. .... .... .... = Speaks 4.1 protocol (old flag): Set
        1... .... .... .... = Can do 4.1 authentication: Set
         */
        $greeting->writeUInt16LE(0xffff);

        // Server Language: utf8 COLLATE utf8_general_ci (33)
        $greeting->writeUInt8(0x21);

        /**
        Server Status: 0x0002
        .... .... .... ...0 = In transaction: Not set
        .... .... .... ..1. = AUTO_COMMIT: Set
        .... .... .... .0.. = More results: Not set
        .... .... .... 0... = Multi query - more resultsets: Not set
        .... .... ...0 .... = Bad index used: Not set
        .... .... ..0. .... = No index used: Not set
        .... .... .0.. .... = Cursor exists: Not set
        .... .... 0... .... = Last row sent: Not set
        .... ...0 .... .... = database dropped: Not set
        .... ..0. .... .... = No backslash escapes: Not set
        .... .0.. .... .... = Session state changed: Not set
        .... 0... .... .... = Query was slow: Not set
        ...0 .... .... .... = PS Out Params: Not set
         */
        $greeting->writeUInt16LE(0x0002);

        /**
        Extended Server Capabilities: 0xc1ff
        .... .... .... ...1 = Multiple statements: Set
        .... .... .... ..1. = Multiple results: Set
        .... .... .... .1.. = PS Multiple results: Set
        .... .... .... 1... = Plugin Auth: Set
        .... .... ...1 .... = Connect attrs: Set
        .... .... ..1. .... = Plugin Auth LENENC Client Data: Set
        .... .... .1.. .... = Client can handle expired passwords: Set
        .... .... 1... .... = Session variable tracking: Set
        .... ...1 .... .... = Deprecate EOF: Set
        1100 000. .... .... = Unused: 0x60
         */
        $greeting->writeUInt16LE(0xc1ff);

        // Authentication Plugin Length: 21
        $greeting->writeUInt8(21);

        // Unused: 00000000000000000000
        $greeting->write(str_repeat("\0", 10));

        // salt
        $greeting->write("$salt\0");

        // Authentication Plugin: mysql_native_password
        $greeting->write("mysql_native_password\0");
    }

    public static function packHeader(Buffer $buffer, $seq = 0)
    {
        $len = $buffer->readableBytes();

        // 3byte len  Packet Num
        // 1 byte seq Packet Length
        $packetHeader = substr(pack('V', $len), 0, 3) . pack('C', $seq % 256);
        $packetBody = $buffer->read(PHP_INT_MAX);
        return [$packetHeader, $packetBody];
    }
}