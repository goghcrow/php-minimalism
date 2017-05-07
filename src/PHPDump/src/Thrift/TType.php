<?php

namespace Minimalism\PHPDump\Thrift;


final class TType extends Enum
{
    const STOP   = 0;
    const VOID   = 1;
    const BOOL   = 2;
    const BYTE   = 3;
    const I08    = 3;
    const DOUBLE = 4;
    const I16    = 6;
    const I32    = 8;
    const I64    = 10;
    const STRING = 11;
    // const UTF7   = 11;
    const STRUCT = 12;
    const MAP    = 13;
    const SET    = 14;
    const LST    = 15;
    const UTF8   = 16;
    const UTF16  = 17;

    // 其他简单类型不会用于网络传输
    private static $basicType = [
        self::BOOL   => 'Bool',
        self::BYTE   => 'Byte',
        self::I16    => 'I16',
        self::I32    => 'I32',
        self::I64    => 'I64',
        self::DOUBLE => 'Double',
        self::STRING => 'String'
    ];

    private static $complexType = [
        self::STRUCT => 'Object',
        self::MAP    => 'Map',
        self::SET    => 'Set',
        self::LST    => 'List',
    ];

    public static function isBasic($type)
    {
        return isset(self::$basicType[$type]);
    }

    public static function getName($type)
    {
        if (isset(self::$basicType[$type])) {
            return self::$basicType[$type];
        }
        if (isset(self::$complexType[$type])) {
            return self::$complexType[$type];
        }
        return "Unknown";
    }
}