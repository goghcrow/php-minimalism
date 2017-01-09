<?php

namespace Minimalism;


/**
 * 修改自PHP-ESAPI中MySQLCodec部分代码
 * 合并MySQLCodec与Codec, 剪裁decode部分, 仅保留最小encode部分代码
 *
 * http://dev.mysql.com/doc/refman/5.7/en/string-literals.html#character-escape-sequences
 *
 * Escape Sequence	Character Represented by Sequence
 * \0	An ASCII NUL (X'00') character
 * \'	A single quote (') character
 * \"	A double quote (") character
 * \b	A backspace character
 * \n	A newline (linefeed) character
 * \r	A carriage return character
 * \t	A tab character
 * \Z	ASCII 26 (Control+Z); see note following the table
 * \\	A backslash (\) character
 * \%	A % character; see note following the table
 * \_	A _ character; see note following the table
 *
 * 因框架实现原因, like 表达式中的通配符是由应用层自行拼接的, 所以由应用层自行转义 %与_
 * 此编码函数不进行处理
 */

/**
 * OWASP Enterprise Security API (ESAPI)
 *
 * This file is part of the Open Web Application Security Project (OWASP)
 * Enterprise Security API (ESAPI) project.
 *
 * LICENSE: This source file is subject to the New BSD license.  You should read
 * and accept the LICENSE before you use, modify, and/or redistribute this
 * software.
 * 
 * PHP version 5.2
 *
 * @category  OWASP
 * @package   ESAPI_Codecs
 * @author    Arnaud Labenne <arnaud.labenne@dotsafe.fr>
 * @author    Mike Boberski <boberski_michael@bah.com>
 * @copyright 2009-2010 The OWASP Foundation
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD license
 * @version   SVN: $Id$
 * @link      http://www.owasp.org/index.php/ESAPI
 */

/**
 *
 * @category  OWASP
 * @package   ESAPI_Codecs
 * @author    Arnaud Labenne <arnaud.labenne@dotsafe.fr>
 * @author    Mike Boberski <boberski_michael@bah.com>
 * @copyright 2009-2010 The OWASP Foundation
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD license
 * @version   Release: @package_version@
 * @link      http://www.owasp.org/index.php/ESAPI
 */
class MySQLCodec
{
    const MYSQL_ANSI = 0;
    const MYSQL_STD = 1;

    /**
     * An map where the keys are ordinal values of non-alphanumeric single-byte
     * characters and the values are hexadecimal equivalents as strings.
     */
    private static $_hex = [];

    private $_mode;

    /**
     * Encode input for use in a SQL query, according to the selected codec
     * (appropriate codecs include the MySQLCodec and OracleCodec).
     *
     * This method is not recommended. The use of the PreparedStatement
     * interface is the preferred approach. However, if for some reason this is
     * impossible, then this method is provided as a weaker alternative.
     *
     * @param string $input string to encode for use in a SQL query.
     *
     * @return string the input string encoded for use in a SQL query.
     *
     * 咨询过杨一, 我们现在用的是 MYSQL ANSI
     * 但是经过测试使用MYSQL_ANSI, 构造出来的sql 会出现语法错误
     */
    public static function encodeForSQL($input)
    {
        if ($input === null) {
            return null;
        }

        $mysqlCodec = new MySQLCodec(MySQLCodec::MYSQL_STD);
        $_immune_sql = [' '];
        return $mysqlCodec->encode($_immune_sql, $input);
    }

    /**
     * Public Constructor 
     * 
     * @param int $mode Mode has to be one of {MYSQL_MODE|ANSI_MODE} to allow 
     *                  correct encoding
     */
    public function __construct($mode = self::MYSQL_STD)
    {
        // Populates the $hex map of non-alphanumeric single-byte characters.
        for ($i = 0; $i < 256; $i++) {
            if (($i >= 48 && $i <= 57)
                || ($i >= 65 && $i <= 90)
                || ($i >= 97 && $i <= 122)
            ) {
                self::$_hex[$i] = null;
            } else {
                self::$_hex[$i] = self::toHex($i);
            }
        }

        $this->_mode = $mode;
    }

    /**
     * Encode a String with a Codec.
     *
     * @param array $immune immune characters
     * @param string $input  the String to encode.
     *
     * @return string the encoded string.
     */
    public function encode(array $immune, $input)
    {
        $encoding      = self::detectEncoding($input);
        $mbstrlen      = mb_strlen($input, $encoding);
        $encodedString = mb_convert_encoding("", $encoding);
        for ($i = 0; $i < $mbstrlen; $i++) {
            $c = mb_substr($input, $i, 1, $encoding);
            $encodedString .= $this->encodeCharacter($immune, $c);
        }
        return $encodedString;
    }

    /**
     * Utility to detect a (potentially multibyte) string's encoding with
     * extra logic to deal with single characters that mb_detect_encoding() fails
     * upon.
     *
     * @param string $string string to examine
     *
     * @return string returns detected encoding
     */
    public static function detectEncoding($string)
    {
        // detect encoding, special-handling for chr(172) and chr(128) to
        //chr(159) which fail to be detected by mb_detect_encoding()
        $is_single_byte = false;
        $bytes = unpack('C*', $string);
        if (is_array($bytes) && sizeof($bytes, 0) == 1) {
            $is_single_byte = true;
        }

        if ($is_single_byte === false) {
            // NoOp
        } else if ((ord($string) == 172)
            || (ord($string) >= 128 && ord($string) <= 159)
        ) {
            // although these chars are beyond ASCII range, if encoding is
            // forced to ISO-8859-1 they will all encode to &#x31;
            return 'ASCII'; //
        } else if (ord($string) >= 160 && ord($string) <= 255) {
            return 'ISO-8859-1';
        }

        // Strict encoding detection with fallback to non-strict detection.
        if (mb_detect_encoding($string, 'UTF-32', true)) {
            return 'UTF-32';
        } else if (mb_detect_encoding($string, 'UTF-16', true)) {
            return 'UTF-16';
        } else if (mb_detect_encoding($string, 'UTF-8', true)) {
            return 'UTF-8';
        } else if (mb_detect_encoding($string, 'ISO-8859-1', true)) {
            // To try an catch strings containing mixed encoding, search
            // the string for chars of ordinal in the range 128 to 159 and
            // 172 and don't return ISO-8859-1 if present.
            $limit = mb_strlen($string, 'ISO-8859-1');
            for ($i = 0; $i < $limit; $i++) {
                $char = mb_substr($string, $i, 1, 'ISO-8859-1');
                if ( (ord($char) == 172)
                    || (ord($char) >= 128 && ord($char) <= 159)
                ) {
                    return 'UTF-8';
                }
            }
            return 'ISO-8859-1';
        } else if (mb_detect_encoding($string, 'ASCII', true)) {
            return 'ASCII';
        } else {
            return mb_detect_encoding($string);
        }
    }

    /**
     * Encode a Character with a Codec.
     *
     * @param array $immune immune characters
     * @param string $c      the Character to encode.
     *
     * @return string the encoded Character.
     */
    public function encodeCharacter(array $immune, $c)
    {
        //detect encoding, special-handling for chr(172) and chr(128) to chr(159)
        //which fail to be detected by mb_detect_encoding()
        $initialEncoding = $this->detectEncoding($c);

        // Normalize encoding to UTF-32
        $_4ByteUnencodedOutput = $this->normalizeEncoding($c);

        // Start with nothing; format it to match the encoding of the string passed
        //as an argument.
        $encodedOutput = mb_convert_encoding("", $initialEncoding);

        // Grab the 4 byte character.
        $_4ByteCharacter = $this->forceToSingleCharacter($_4ByteUnencodedOutput);

        // Get the ordinal value of the character.
        list(, $ordinalValue) = unpack("N", $_4ByteCharacter);

        // check for immune characters
        foreach ($immune as $immuneCharacter) {
            // Convert to UTF-32 (4 byte characters, regardless of actual number of
            //bytes in the character).
            $_4ByteImmuneCharacter = $this->normalizeEncoding($immuneCharacter);

            // Ensure it's a single 4 byte character (since $immune is an array of
            //strings) by grabbing only the 1st multi-byte character.
            $_4ByteImmuneCharacter = $this->forceToSingleCharacter(
                $_4ByteImmuneCharacter
            );

            // If the character is immune then return it.
            if ($_4ByteCharacter === $_4ByteImmuneCharacter) {
                return $encodedOutput . chr($ordinalValue);
            }
        }

        // Check for alphanumeric characters
        $hex = $this->getHexForNonAlphanumeric($_4ByteCharacter);
        if ($hex === null) {
            return $encodedOutput . chr($ordinalValue);
        }

        switch ($this->_mode) {
            case self::MYSQL_ANSI:
                return $encodedOutput . $this->_encodeCharacterANSI($c);
            case self::MYSQL_STD:
                return $encodedOutput . $this->_encodeCharacterMySQL($c);
        }

        //Mode has an incorrect value
        return $encodedOutput . chr($ordinalValue);
    }

    /**
     * encodeCharacterANSI encodes for ANSI SQL.
     *
     * Only the apostrophe is encoded
     *
     * @param string $c Character to encode
     *
     * @return string '' if ', otherwise return c directly
     */
    private function _encodeCharacterANSI($c)
    {
        // Normalize encoding to UTF-32
        $_4ByteUnencodedOutput = $this->normalizeEncoding($c);

        // Grab the 4 byte character
        $_4ByteCharacter = $this->forceToSingleCharacter($_4ByteUnencodedOutput);

        // Get the ordinal value of the character.
        list(, $ordinalValue) = unpack("N", $_4ByteCharacter);

        //If the character is a quote
        if ($ordinalValue == 0x27) {
            return $c . $c;
        } else {
            return $c;
        }
    }

    /**
     * Encode a character suitable for MySQL
     *
     * @param string $c Character to encode
     *
     * @return string Encoded Character
     */
    private function _encodeCharacterMySQL($c)
    {
        // Normalize encoding to UTF-32
        $_4ByteUnencodedOutput = $this->normalizeEncoding($c);

        // Grab the 4 byte character
        $_4ByteCharacter = $this->forceToSingleCharacter($_4ByteUnencodedOutput);

        // Get the ordinal value of the character.
        list(, $ordinalValue) = unpack("N", $_4ByteCharacter);

        switch ($ordinalValue) {
            case 0x00:
                return "\\0";
            case 0x08:
                return "\\b";
            case 0x09:
                return "\\t";
            case 0x0a:
                return "\\n";
            case 0x0d:
                return "\\r";
            case 0x1a:
                return "\\Z";
            case 0x22:
                return "\\\"";
            case 0x25:
                // 取消对like wildcard的处理, 原因参见文件开头注释
                // return "\\%";
                return "%";
            case 0x27:
                return "\\'";
            case 0x5c:
                return "\\\\";
            case 0x5f:
                // 取消对like wildcard的处理, 原因参见文件开头注释
                // return "\\_";
                return "_";
        }

        return '\\' . $c;
    }

    /**
     * Utility to normalize a string's encoding to UTF-32.
     *
     * @param string $string string to normalize
     *
     * @return string normalized string
     */
    public static function normalizeEncoding($string)
    {
        // Convert to UTF-32 (4 byte characters, regardless of actual number of
        //bytes in the character).
        $initialEncoding = self::detectEncoding($string);

        $encoded = mb_convert_encoding($string, "UTF-32", $initialEncoding);

        return $encoded;
    }

    /**
     * Utility to get first (potentially multibyte) character from a (potentially
     * multicharacter) multibyte string.
     *
     * @param string $string string to convert
     *
     * @return string converted string
     */
    public static function forceToSingleCharacter($string)
    {
        // Grab first character from UTF-32 encoded string
        return mb_substr($string, 0, 1, "UTF-32");
    }

    /**
     * Returns the ordinal value as a hex string of any character that is not a
     * single-byte alphanumeric. The character should be supplied as a string in
     * the UTF-32 character encoding.
     * If the character is an alphanumeric character with ordinal value below
     * 255 then this method will return null.
     *
     * @param string $c 4 byte character character.
     *
     * @return string hexadecimal ordinal value of non-alphanumeric characters
     *                or null otherwise.
     */
    public static function getHexForNonAlphanumeric($c)
    {
        // Assumption/prerequisite: $c is a UTF-32 encoded string
        $_4ByteString = $c;

        // Grab the 4 byte character.
        $_4ByteCharacter = self::forceToSingleCharacter($_4ByteString);

        // Get the ordinal value of the character.
        list(, $ordinalValue) = unpack("N", $_4ByteCharacter);

        if ($ordinalValue <= 255) {
            return self::$_hex[$ordinalValue];
        }
        return self::toHex($ordinalValue);
    }

    /**
     * Return the hex value of a character as a string without leading zeroes.
     *
     * @param string $c character to convert
     *
     * @return int returns hex value
     */
    public static function toHex($c)
    {
        // Assumption/prerequisite: $c is the ordinal value of the character
        // (i.e. an integer)
        return dechex($c);
    }
}