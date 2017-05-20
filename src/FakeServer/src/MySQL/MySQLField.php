<?php

namespace Minimalism\FakeServer\MySQL;


/**
 * Class MySQLField
 * @package Minimalism\FakeServer\MySQL
 *
 * 字节	说明
 * n	目录名称（Length Coded String） 在4.1及之后的版本中，该字段值为"def"。
 * n	数据库名称（Length Coded String） 数据库名称标识。
 * n	数据表名称（Length Coded String） 数据表的别名（AS之后的名称）。
 * n	数据表原始名称（Length Coded String） 数据表的原始名称（AS之前的名称）。
 * n	列（字段）名称（Length Coded String） 列（字段）的别名（AS之后的名称）。
 * n	列（字段）原始名称（Length Coded String） 列（字段）的原始名称（AS之前的名称）。
 * 1	填充值
 * 2	字符编码 列（字段）的字符编码值。
 * 4	列（字段）长度 列（字段）的长度值，真实长度可能小于该值，例如VARCHAR(2)类型的字段实际只能存储1个字符。
 * 1	列（字段）类型 列（字段）的类型值
 * 2	列（字段）标志
 * 1	整型值精度 该字段对DECIMAL和NUMERIC类型的数值字段有效，用于标识数值的精度（小数点位置）。
 * 2	填充值（0x00） 该字段用在数据表定义中，普通的查询结果中不会出现。
 * n	默认值（Length Coded String）
 *
 * 附：Field结构的相关处理函数：
 * 客户端：/client/client.c源文件中的unpack_fields函数
 * 服务器：/sql/sql_base.cc源文件中的send_fields函数
 */
class MySQLField
{
    // 列（字段）类型：列（字段）的类型值，取值范围如下（参考源代码/include/mysql_com.h头文件中的enum_field_type枚举类型定义）：

    const FIELD_TYPE_DECIMAL    = 0x00;
    const FIELD_TYPE_TINY       = 0x01;
    const FIELD_TYPE_SHORT      = 0x02;
    const FIELD_TYPE_LONG       = 0x03;
    const FIELD_TYPE_FLOAT      = 0x04;
    const FIELD_TYPE_DOUBLE     = 0x05;
    const FIELD_TYPE_NULL       = 0x06;
    const FIELD_TYPE_TIMESTAMP  = 0x07;
    const FIELD_TYPE_LONGLONG   = 0x08;
    const FIELD_TYPE_INT24      = 0x09;
    const FIELD_TYPE_DATE       = 0x0A;
    const FIELD_TYPE_TIME       = 0x0B;
    const FIELD_TYPE_DATETIME   = 0x0C;
    const FIELD_TYPE_YEAR       = 0x0D;
    const FIELD_TYPE_NEWDATE    = 0x0E;
    const FIELD_TYPE_VARCHAR    = 0x0F;
    const FIELD_TYPE_BIT        = 0x10;
    const FIELD_TYPE_NEWDECIMAL = 0xF6;
    const FIELD_TYPE_ENUM       = 0xF7;
    const FIELD_TYPE_SET        = 0xF8;
    const FIELD_TYPE_TINY_BLOB  = 0xF9;
    const FIELD_TYPE_MEDIUM_BLOB= 0xFA;
    const FIELD_TYPE_LONG_BLOB  = 0xFB;
    const FIELD_TYPE_BLOB       = 0xFC;
    const FIELD_TYPE_VAR_STRING = 0xFD;
    const FIELD_TYPE_STRING     = 0xFE;
    const FIELD_TYPE_GEOMETRY   = 0xFF;


    // 列（字段）标志：各标志位定义如下（参考源代码/include/mysql_com.h头文件中的宏定义）：

    const NOT_NULL_FLAG         = 0x0001;
    const PRI_KEY_FLAG          = 0x0002;
    const UNIQUE_KEY_FLAG       = 0x0004;
    const MULTIPLE_KEY_FLAG     = 0x0008;
    const BLOB_FLAG             = 0x0010;
    const UNSIGNED_FLAG         = 0x0020;
    const ZEROFILL_FLAG         = 0x0040;
    const BINARY_FLAG           = 0x0080;
    const ENUM_FLAG             = 0x0100;
    const AUTO_INCREMENT_FLAG   = 0x0200;
    const TIMESTAMP_FLAG        = 0x0400;
    const SET_FLAG              = 0x0800;


    //Name
    public $catalog = "def";
    public $database;
    public $table;
    public $originalTable;
    public $name;
    public $originalName;
    public $charset = MySQLBinaryStream::CHARSET;
    public $length;
    public $type;
    public $flags = 0;
    public $decimals = 0;
    public $default = "";

    /**
    Flags: 0x0001
    .... .... .... ...1 = Not null: Set
    .... .... .... ..0. = Primary key: Not set
    .... .... .... .0.. = Unique key: Not set
    .... .... .... 0... = Multiple key: Not set
    .... .... ...0 .... = Blob: Not set
    .... .... ..0. .... = Unsigned: Not set
    .... .... .0.. .... = Zero fill: Not set
    .... .... 0... .... = Binary: Not set
    .... ...0 .... .... = Enum: Not set
    .... ..0. .... .... = Auto increment: Not set
    .... .0.. .... .... = Timestamp: Not set
    .... 0... .... .... = Set: Not set
     */
}