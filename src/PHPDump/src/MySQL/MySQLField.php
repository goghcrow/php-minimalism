<?php

namespace Minimalism\PHPDump\MySQL;


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

    private static $typeMap = [
        self::FIELD_TYPE_NULL        => "null",

        self::FIELD_TYPE_BIT         => "bit",
        self::FIELD_TYPE_TINY        => "tinyint",
        self::FIELD_TYPE_SHORT       => "smallint",
        self::FIELD_TYPE_INT24       => "mediumint",
        self::FIELD_TYPE_LONG        => "int",
        self::FIELD_TYPE_LONGLONG    => "bigint",


        self::FIELD_TYPE_DECIMAL     => "decimal", // decimal or numeric
        self::FIELD_TYPE_FLOAT       => "float",
        self::FIELD_TYPE_DOUBLE      => "double", // double or real
        self::FIELD_TYPE_NEWDECIMAL  => "newdecimal",


        self::FIELD_TYPE_TIMESTAMP   => "timestamp",
        self::FIELD_TYPE_DATE        => "date",
        self::FIELD_TYPE_TIME        => "time",
        self::FIELD_TYPE_DATETIME    => "datetime",
        self::FIELD_TYPE_YEAR        => "year",
        self::FIELD_TYPE_NEWDATE     => "newdate",

        self::FIELD_TYPE_ENUM        => "enum",
        self::FIELD_TYPE_SET         => "set",

        self::FIELD_TYPE_VARCHAR     => "varchar", // ?

        self::FIELD_TYPE_GEOMETRY    => "geometry",


        // 旧字段
        self::FIELD_TYPE_TINY_BLOB   => "tinyblob",
        self::FIELD_TYPE_MEDIUM_BLOB => "mediumblob",
        self::FIELD_TYPE_BLOB        => "blob", // text
        self::FIELD_TYPE_LONG_BLOB   => "longblob",
        self::FIELD_TYPE_VAR_STRING  => "varchar",
        self::FIELD_TYPE_STRING      => "char", // char or varchar (由BINARY_FLAG决定是否是bin)
    ];


    // 列（字段）标志：各标志位定义如下（参考源代码/include/mysql_com.h头文件中的宏定义）：

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

    const NOT_NULL_FLAG         = 0x0001; // If defined, the field cannot contain a NULL value.
    const PRI_KEY_FLAG          = 0x0002; // If defined, the field is a primary key.
    const UNIQUE_KEY_FLAG       = 0x0004; // If defined, the field is part of a unique key.
    const MULTIPLE_KEY_FLAG     = 0x0008; // If defined, the field is part of a key.
    const BLOB_FLAG             = 0x0010; // If defined, the field is of type BLOB or TEXT.
    const UNSIGNED_FLAG         = 0x0020; // If defined, the field is a numeric type with an unsigned value.
    const ZEROFILL_FLAG         = 0x0040; // If defined, the application should fill any unused characters in a value of this field with zeros.
    const BINARY_FLAG           = 0x0080; // If defined, the field is of type CHAR or VARCHAR with the BINARY flag.
    const ENUM_FLAG             = 0x0100; // If defined, the field is of type ENUM.
    const AUTO_INCREMENT_FLAG   = 0x0200; // If defined, the field has the AUTO_INCREMENT attribute.
    const TIMESTAMP_FLAG        = 0x0400; // If defined, the field is of type TIMESTAMP.
    const SET_FLAG              = 0x0800; // If defined, the field is of type SET.

    private static $flagsMap = [
        self::NOT_NULL_FLAG         => "NotNull",
        self::PRI_KEY_FLAG          => "PrimaryKey",
        self::UNIQUE_KEY_FLAG       => "UniqueKey",
        self::MULTIPLE_KEY_FLAG     => "MultipleKey",
        self::BLOB_FLAG             => "Blob",
        self::UNSIGNED_FLAG         => "Unsigned",
        self::ZEROFILL_FLAG         => "Zerofill",
        self::BINARY_FLAG           => "Binary",
        self::ENUM_FLAG             => "Enum",
        self::AUTO_INCREMENT_FLAG   => "AutoIncrement",
        self::TIMESTAMP_FLAG        => "Timestamp",
        self::SET_FLAG              => "Set",
    ];

    /* collation codes may change over time, recreate with the following SQL

    SELECT CONCAT('  {', ID, ',"', CHARACTER_SET_NAME, ' COLLATE ', COLLATION_NAME, '",')
    FROM INFORMATION_SCHEMA.COLLATIONS
    ORDER BY ID
    INTO OUTFILE '/tmp/mysql-collations';

    */
    private static $charsetMap = [
        3 => "dec8 COLLATE dec8_swedish_ci",
        4 => "cp850 COLLATE cp850_general_ci",
        5 => "latin1 COLLATE latin1_german1_ci",
        6 => "hp8 COLLATE hp8_english_ci",
        7 => "koi8r COLLATE koi8r_general_ci",
        8 => "latin1 COLLATE latin1_swedish_ci",
        9 => "latin2 COLLATE latin2_general_ci",
        10 => "swe7 COLLATE swe7_swedish_ci",
        11 => "ascii COLLATE ascii_general_ci",
        14 => "cp1251 COLLATE cp1251_bulgarian_ci",
        15 => "latin1 COLLATE latin1_danish_ci",
        16 => "hebrew COLLATE hebrew_general_ci",
        20 => "latin7 COLLATE latin7_estonian_cs",
        21 => "latin2 COLLATE latin2_hungarian_ci",
        22 => "koi8u COLLATE koi8u_general_ci",
        23 => "cp1251 COLLATE cp1251_ukrainian_ci",
        25 => "greek COLLATE greek_general_ci",
        26 => "cp1250 COLLATE cp1250_general_ci",
        27 => "latin2 COLLATE latin2_croatian_ci",
        29 => "cp1257 COLLATE cp1257_lithuanian_ci",
        30 => "latin5 COLLATE latin5_turkish_ci",
        31 => "latin1 COLLATE latin1_german2_ci",
        32 => "armscii8 COLLATE armscii8_general_ci",
        33 => "utf8 COLLATE utf8_general_ci",
        36 => "cp866 COLLATE cp866_general_ci",
        37 => "keybcs2 COLLATE keybcs2_general_ci",
        38 => "macce COLLATE macce_general_ci",
        39 => "macroman COLLATE macroman_general_ci",
        40 => "cp852 COLLATE cp852_general_ci",
        41 => "latin7 COLLATE latin7_general_ci",
        42 => "latin7 COLLATE latin7_general_cs",
        43 => "macce COLLATE macce_bin",
        44 => "cp1250 COLLATE cp1250_croatian_ci",
        45 => "utf8mb4 COLLATE utf8mb4_general_ci",
        46 => "utf8mb4 COLLATE utf8mb4_bin",
        47 => "latin1 COLLATE latin1_bin",
        48 => "latin1 COLLATE latin1_general_ci",
        49 => "latin1 COLLATE latin1_general_cs",
        50 => "cp1251 COLLATE cp1251_bin",
        51 => "cp1251 COLLATE cp1251_general_ci",
        52 => "cp1251 COLLATE cp1251_general_cs",
        53 => "macroman COLLATE macroman_bin",
        57 => "cp1256 COLLATE cp1256_general_ci",
        58 => "cp1257 COLLATE cp1257_bin",
        59 => "cp1257 COLLATE cp1257_general_ci",
        63 => "binary COLLATE binary",
        64 => "armscii8 COLLATE armscii8_bin",
        65 => "ascii COLLATE ascii_bin",
        66 => "cp1250 COLLATE cp1250_bin",
        67 => "cp1256 COLLATE cp1256_bin",
        68 => "cp866 COLLATE cp866_bin",
        69 => "dec8 COLLATE dec8_bin",
        70 => "greek COLLATE greek_bin",
        71 => "hebrew COLLATE hebrew_bin",
        72 => "hp8 COLLATE hp8_bin",
        73 => "keybcs2 COLLATE keybcs2_bin",
        74 => "koi8r COLLATE koi8r_bin",
        75 => "koi8u COLLATE koi8u_bin",
        77 => "latin2 COLLATE latin2_bin",
        78 => "latin5 COLLATE latin5_bin",
        79 => "latin7 COLLATE latin7_bin",
        80 => "cp850 COLLATE cp850_bin",
        81 => "cp852 COLLATE cp852_bin",
        82 => "swe7 COLLATE swe7_bin",
        83 => "utf8 COLLATE utf8_bin",
        92 => "geostd8 COLLATE geostd8_general_ci",
        93 => "geostd8 COLLATE geostd8_bin",
        94 => "latin1 COLLATE latin1_spanish_ci",
        99 => "cp1250 COLLATE cp1250_polish_ci",
        192 => "utf8 COLLATE utf8_unicode_ci",
        193 => "utf8 COLLATE utf8_icelandic_ci",
        194 => "utf8 COLLATE utf8_latvian_ci",
        195 => "utf8 COLLATE utf8_romanian_ci",
        196 => "utf8 COLLATE utf8_slovenian_ci",
        197 => "utf8 COLLATE utf8_polish_ci",
        198 => "utf8 COLLATE utf8_estonian_ci",
        199 => "utf8 COLLATE utf8_spanish_ci",
        200 => "utf8 COLLATE utf8_swedish_ci",
        201 => "utf8 COLLATE utf8_turkish_ci",
        202 => "utf8 COLLATE utf8_czech_ci",
        203 => "utf8 COLLATE utf8_danish_ci",
        204 => "utf8 COLLATE utf8_lithuanian_ci",
        205 => "utf8 COLLATE utf8_slovak_ci",
        206 => "utf8 COLLATE utf8_spanish2_ci",
        207 => "utf8 COLLATE utf8_roman_ci",
        208 => "utf8 COLLATE utf8_persian_ci",
        209 => "utf8 COLLATE utf8_esperanto_ci",
        210 => "utf8 COLLATE utf8_hungarian_ci",
        211 => "utf8 COLLATE utf8_sinhala_ci",
        212 => "utf8 COLLATE utf8_german2_ci",
        213 => "utf8 COLLATE utf8_croatian_ci",
        214 => "utf8 COLLATE utf8_unicode_520_ci",
        215 => "utf8 COLLATE utf8_vietnamese_ci",
        223 => "utf8 COLLATE utf8_general_mysql500_ci",
        224 => "utf8mb4 COLLATE utf8mb4_unicode_ci",
        225 => "utf8mb4 COLLATE utf8mb4_icelandic_ci",
        226 => "utf8mb4 COLLATE utf8mb4_latvian_ci",
        227 => "utf8mb4 COLLATE utf8mb4_romanian_ci",
        228 => "utf8mb4 COLLATE utf8mb4_slovenian_ci",
        229 => "utf8mb4 COLLATE utf8mb4_polish_ci",
        230 => "utf8mb4 COLLATE utf8mb4_estonian_ci",
        231 => "utf8mb4 COLLATE utf8mb4_spanish_ci",
        232 => "utf8mb4 COLLATE utf8mb4_swedish_ci",
        233 => "utf8mb4 COLLATE utf8mb4_turkish_ci",
        234 => "utf8mb4 COLLATE utf8mb4_czech_ci",
        235 => "utf8mb4 COLLATE utf8mb4_danish_ci",
        236 => "utf8mb4 COLLATE utf8mb4_lithuanian_ci",
        237 => "utf8mb4 COLLATE utf8mb4_slovak_ci",
        238 => "utf8mb4 COLLATE utf8mb4_spanish2_ci",
        239 => "utf8mb4 COLLATE utf8mb4_roman_ci",
        240 => "utf8mb4 COLLATE utf8mb4_persian_ci",
        241 => "utf8mb4 COLLATE utf8mb4_esperanto_ci",
        242 => "utf8mb4 COLLATE utf8mb4_hungarian_ci",
        243 => "utf8mb4 COLLATE utf8mb4_sinhala_ci",
        244 => "utf8mb4 COLLATE utf8mb4_german2_ci",
        245 => "utf8mb4 COLLATE utf8mb4_croatian_ci",
        246 => "utf8mb4 COLLATE utf8mb4_unicode_520_ci",
        247 => "utf8mb4 COLLATE utf8mb4_vietnamese_ci",
    ];

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

    public $default;

    public function __debugInfo()
    {
        $charset = $this->getCharset();
        $type = $this->getType();
        $flags = $this->getFlags();

        $name = "$this->database.$this->table.$this->name";

        return [
            "name" => $name,
            "type" => "$type($this->length)",
            "flags" => $flags,
            "charset" => $charset,
            "def" => $this->default,
        ];
    }

    public function __toString()
    {
        $type = $this->getType();
        $flags = $this->getFlags();

        $name = "$this->database.$this->table.$this->name";


        if ($this->default !== null) {
            return "$name $type($this->length) $flags def={$this->default}";
        } else {
            return "$name $type($this->length) $flags";
        }
    }

    public function fmtValue($value)
    {
        switch ($this->type) {
            case self::FIELD_TYPE_NULL       :
                return $value; // "null"

            case self::FIELD_TYPE_DECIMAL    :
            case self::FIELD_TYPE_FLOAT      :
            case self::FIELD_TYPE_DOUBLE     :
            case self::FIELD_TYPE_NEWDECIMAL :
                return floatval($value);


            case self::FIELD_TYPE_BIT        :
            case self::FIELD_TYPE_TINY       :
            case self::FIELD_TYPE_SHORT      :
            case self::FIELD_TYPE_INT24      :
            case self::FIELD_TYPE_LONG       :
                return intval($value);

            case self::FIELD_TYPE_TINY_BLOB  :
            case self::FIELD_TYPE_MEDIUM_BLOB:
            case self::FIELD_TYPE_LONG_BLOB  :
            case self::FIELD_TYPE_BLOB       :
            case self::FIELD_TYPE_VAR_STRING :
            case self::FIELD_TYPE_STRING     :
            case self::FIELD_TYPE_VARCHAR    :
                return strval($value);

            case self::FIELD_TYPE_TIMESTAMP  :
            case self::FIELD_TYPE_LONGLONG   :
            case self::FIELD_TYPE_DATE       :
            case self::FIELD_TYPE_TIME       :
            case self::FIELD_TYPE_DATETIME   :
            case self::FIELD_TYPE_YEAR       :
            case self::FIELD_TYPE_NEWDATE    :

            case self::FIELD_TYPE_ENUM       :
            case self::FIELD_TYPE_SET        :

            case self::FIELD_TYPE_GEOMETRY   :
            default:
                return $value;
        }
    }

    public function getCharset()
    {
        if (isset(self::$charsetMap[$this->charset])) {
            return self::$charsetMap[$this->charset];
        } else {
            return "unknown charset";
        }
    }

    public function getType()
    {
        if (isset(self::$typeMap[$this->type])) {
            $type = self::$typeMap[$this->type];
            if ($this->type === self::FIELD_TYPE_DECIMAL || $this->type === self::FIELD_TYPE_NEWDECIMAL) {
                return "$type($this->decimals)";
            } else {
                return $type;
            }
        } else {
            return "unknown type";
        }
    }

    public function getFlags()
    {
        $flagsDesc = [];
        if ($this->flags & self::PRI_KEY_FLAG       ) {
            $flagsDesc[] = self::$flagsMap[self::PRI_KEY_FLAG];
        }
        if ($this->flags & self::AUTO_INCREMENT_FLAG) {
            $flagsDesc[] = self::$flagsMap[self::AUTO_INCREMENT_FLAG];
        }
        if ($this->flags & self::UNSIGNED_FLAG      ) {
            $flagsDesc[] = self::$flagsMap[self::UNSIGNED_FLAG];
        }
        if ($this->flags & self::NOT_NULL_FLAG      ) {
            $flagsDesc[] = self::$flagsMap[self::NOT_NULL_FLAG];
        }
        if ($this->flags & self::UNIQUE_KEY_FLAG    ) {
            $flagsDesc[] = self::$flagsMap[self::UNIQUE_KEY_FLAG];
        }
        if ($this->flags & self::MULTIPLE_KEY_FLAG  ) {
            $flagsDesc[] = self::$flagsMap[self::MULTIPLE_KEY_FLAG];
        }
        if ($this->flags & self::BLOB_FLAG          ) {
            $flagsDesc[] = self::$flagsMap[self::BLOB_FLAG];
        }
        if ($this->flags & self::ZEROFILL_FLAG      ) {
            $flagsDesc[] = self::$flagsMap[self::ZEROFILL_FLAG];
        }
        if ($this->flags & self::BINARY_FLAG        ) {
            $flagsDesc[] = self::$flagsMap[self::BINARY_FLAG];
        }
        if ($this->flags & self::ENUM_FLAG          ) {
            $flagsDesc[] = self::$flagsMap[self::ENUM_FLAG];
        }
        if ($this->flags & self::TIMESTAMP_FLAG     ) {
            $flagsDesc[] = self::$flagsMap[self::TIMESTAMP_FLAG];
        }
        if ($this->flags & self::SET_FLAG           ) {
            $flagsDesc[] = self::$flagsMap[self::SET_FLAG];
        }

        return implode(" ", $flagsDesc);
    }
}