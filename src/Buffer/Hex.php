<?php

namespace Minimalism\Buffer;


/**
 * Class Hex
 * @author xiaofeng
 */
class Hex
{
    /**
     * @param string $str
     * @param string $fmt
     *
     * fmt 由3部分构成, 中间用  / 分隔, 可省略
     * fmt构成: dump类型v|vv|vvv / nColumns / nHexs
     *
     * e.g. dump($str, "vvv/8/4") // tcpdump 格式打印
     */
    public static function dump($str, $fmt = "")
    {
        // 填充默认值 8 4
        list($v, $nGroups, $perGroup, ) = explode("/", "$fmt/8/4");
        $sep = " ";

        switch ($v) {
            case "v":
                fwrite(STDERR, static::v($str, $nGroups, $perGroup, $sep));
                break;

            case "vv":
                fwrite(STDERR, static::vv($str, $nGroups, $perGroup, $sep));
                break;

            case "vvv":
                fwrite(STDERR, static::vvv($str, $nGroups, $perGroup, $sep));
                break;

            default:
                simple:
                $addPrefix = function($v) { return "0x$v"; };
                fwrite(STDERR, implode($sep, array_map($addPrefix, str_split(bin2hex($str), 2))));
        }
    }

    /**
     * 简单格式
     * @param string $str
     * @param int $nCols n columns
     * @param int $nHexs n hexs per column
     * @param string $sep separation between column
     * @return string
     */
    public static function v($str, $nCols = 16, $nHexs = 2, $sep = " ")
    {
        $nHexs = $nHexs % 2 === 0 ? $nHexs : $nHexs + 1;
        $hexLines = str_split(bin2hex($str), $nCols * $nHexs);

        $buffer = "";
        foreach ($hexLines as $i => $line) {
            $buffer .= static::split($line, $nHexs, $sep) . PHP_EOL;
        }

        return $buffer;
    }

    /**
     * 上下对照格式
     * @param string $str
     * @param int $nCols n columns
     * @param int $nHexs n hexs per column
     * @param string $sep separation between column
     * @param string $placeholder placeholder for invisible char
     * @return string
     */
    public static function vv($str, $nCols = 16, $nHexs = 2, $sep = " ", $placeholder = ".")
    {
        // 两个hex一个char, 必须凑成偶数
        $nHexs = $nHexs % 2 === 0 ? $nHexs : $nHexs + 1;
        $halfPerGroup = $nHexs / 2;

        $hexLines = str_split(bin2hex($str), $nCols * $nHexs);
        $charLines = str_split(static::toASCII($str, $placeholder), $nCols * $halfPerGroup);

        $buffer = "";
        foreach ($hexLines as $i => $line) {
            $buffer .= static::split($line, $nHexs, $sep) . PHP_EOL;
            $buffer .= static::split($charLines[$i], $halfPerGroup, str_repeat(" ", $halfPerGroup) . $sep) . PHP_EOL;
        }

        return $buffer;
    }

    /**
     * tcpdump格式
     * @param string $str
     * @param int $nCols n columns
     * @param int $nHexs n hexs per column
     * @param string $sep separation between column
     * @param string $placeholder placeholder for invisible char
     * @return string
     */
    public static function vvv($str, $nCols = 16, $nHexs = 2, $sep = " ", $placeholder = ".")
    {
        // 两个hex一个char, 必须凑成偶数
        $nHexs = $nHexs % 2 === 0 ? $nHexs : $nHexs + 1;
        $halfPerGroup = $nHexs / 2;

        $hexLines = str_split(bin2hex($str), $nCols * $nHexs);
        $charLines = str_split(static::toASCII($str, $placeholder), $nCols * $halfPerGroup);

        $lineHexWidth = $nCols * $nHexs + strlen($sep) * ($nCols - 1);

        $buffer = "";

        $offset = 0;
        foreach ($hexLines as $i => $line) {
            $hexs = static::split($line, $nHexs, $sep);
            $chars = $charLines[$i];

            $buffer .= sprintf("0x%06s: %-{$lineHexWidth}s  %s" . PHP_EOL, dechex($offset), $hexs, $chars);
            $offset += $nCols;
        }

        return $buffer;
    }

    private static function split($str, $len, $sep)
    {
        return implode($sep, str_split($str, $len));
    }

    private static function toASCII($str, $placeholder = ".")
    {
        static $from = "";
        static $to = "";

        if ($from == "") {
            for ($char = 0; $char <= 0xFF; $char++) {
                $from .= chr($char);
                $to .= ($char >= 0x20 && $char <= 0x7E) ? chr($char) : $placeholder;
            }
        }

        return strtr($str, $from, $to);
    }
}