<?php

namespace Minimalism;


/**
 *       ╔╦╗┌─┐┬─┐┌┬┐┬┌┐┌┌─┐┬
 * Class  ║ ├┤ ├┬┘│││││││├─┤│
 *        ╩ └─┘┴└─┴ ┴┴┘└┘┴ ┴┴─┘
 * @author xiaofeng
 *
 * ANSI/VT100 Terminal Control Escape Sequences
 * @standard http://www.termsys.demon.co.uk/vtansi.htm
 *
 * class T extends Terminal {}
 * class_alias(Terminal::class, "T");
 * use Terminal as T;
 *
 */
class Terminal
{
    const ESC          = "\033";

    const RESET_ALL    = 0;

    const BRIGHT       = 1;
    const DIM          = 2;
    const UNDERSCORE   = 4;
    const BLINK        = 5;
    const REVERSE      = 7;
    const HIDDEN       = 8;

    const FG_BLACK     = 30;
    const FG_RED       = 31;
    const FG_GREEN     = 32;
    const FG_YELLOW    = 33;
    const FG_BLUE      = 34;
    const FG_MAGENTA   = 35;
    const FG_CYAN      = 36;
    const FG_WHITE     = 37;

    const BG_BLACK     = 40;
    const BG_RED       = 41;
    const BG_GREEN     = 42;
    const BG_YELLOW    = 43;
    const BG_BLUE      = 44;
    const BG_MAGENTA   = 45;
    const BG_CYAN      = 46;
    const BG_WHITE     = 47;

    const ERA_UP            = "1J";
    const ERA_DOWN          = "J";
    const ERA_LEFT          = "1K";
    const ERA_RIGHT         = "K";
    const ERA_LINE          = "2K";
    const ERA_SCREEN        = "2J";

    /**
     * Display
     * @param string $text
     * @param array $attrs
     *
     * Set Attribute Mode Format:
     *  <ESC>[{attr1};...;{attrn}m
     * e.g. \033[4;34;mhello 蓝色下划线文件hello
     * \033[0m
     */
    public static function put($text, ...$attrs) {
        // $text = addslashes($text);
        $resetAll = static::ESC . "[0m";
        $attrStr = implode(";", array_map("intval", $attrs));
        $buffer = static::ESC . "[{$attrStr}m{$text}" . $resetAll;
        echo $buffer;
    }

    /**
     * 手动控制输出格式
     * @param array $attrs
     */
    public static function attr(array $attrs) {
        $attrStr = implode(";", array_map("intval", $attrs));
        echo static::ESC . "[{$attrStr}m";
    }

    /**
     * @param $text
     */
    public static function replace($text)
    {
        $numNewLines = substr_count($text, "\n");
        // 光标移动到第0列
        echo chr(27) . "[0G";
        echo $text;
        // 光标向上移动
        echo chr(27) . "[" . $numNewLines ."A";
    }

    /**
     * 清除
     * @param $attr
     * @param bool $return
     * @return string
     */
    public static function erase($attr, $return = false) {
        $buffer = static::ESC . "[$attr";
        if ($return) {
            return $buffer;
        }
        echo $buffer;
        return "";
    }
}