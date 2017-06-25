<?php

namespace Minimalism;


/**
 *       ╔╦╗┌─┐┬─┐┌┬┐┬┌┐┌┌─┐┬
 * Class  ║ ├┤ ├┬┘│││││││├─┤│
 *        ╩ └─┘┴└─┴ ┴┴┘└┘┴ ┴┴─┘
 * @author xiaofeng
 *
 * https://en.wikipedia.org/wiki/ANSI_escape_code
 * man console_codes
 *
 */
final class Terminal
{
    // OTC 033      DEC 27
    const ESC          = "\033"; // chr(27)

    const RESET        = 0;
    const BRIGHT       = 1;
    const DIM          = 2;
    const UNDERSCORE   = 4;
    const BLINK        = 5;
    const REVERSE      = 7;
    const HIDDEN       = 8;

    // FG +30 BG +40
    const BLACK        = 0;
    const RED          = 1;
    const GREEN        = 2;
    const YELLOW       = 3;
    const BLUE         = 4;
    const MAGENTA      = 5;
    const CYAN         = 6;
    const WHITE        = 7;

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

    /**
     * Moves the cursor up by the specified number of lines without changing columns.
     * If the cursor is already on the top line, ANSI.SYS ignores this sequence.
     *
     * @param int $n
     *
     * Esc[ValueA
     */
    public function cursorUp($n = 1)
    {
        echo "\033[{$n}A";
    }

    /**
     * Moves the cursor down by the specified number of lines without changing columns.
     * If the cursor is already on the bottom line, ANSI.SYS ignores this sequence.
     *
     * @param int $n
     *
     * Esc[ValueB
     */
    public function cursorDown($n = 1)
    {
        echo "\033[{$n}B";
    }

    /**
     * Moves the cursor forward by the specified number of columns without changing lines.
     * If the cursor is already in the rightmost column, ANSI.SYS ignores this sequence.
     *
     * @param $n
     *
     * Esc[ValueC
     */
    public function cursorForward($n = 1)
    {
        echo "\033[{$n}C";
    }

    /**
     * Moves the cursor back by the specified number of columns without changing lines.
     * If the cursor is already in the leftmost column, ANSI.SYS ignores this sequence.
     *
     * @param $n
     *
     * Esc[ValueD
     */
    public function cursorBackward($n = 1)
    {
        echo "\033[{$n}D";
    }

    /**
     * Moves cursor to beginning of the line n (default 1) lines down. (not ANSI.SYS)
     * @param int $n
     */
    public function cursorNextLine($n = 1)
    {
        echo "\033[{$n}E";
    }

    /**
     * Moves cursor to beginning of the line n (default 1) lines up. (not ANSI.SYS)
     * @param int $n
     */
    public function cursorPreviousLine($n = 1)
    {
        echo "\033[{$n}F";
    }

    /**
     * Moves the cursor to column n (default 1). (not ANSI.SYS)
     * @param int $n
     * 起始地址应该是1
     */
    public function cursorHorizontalAbsolute($n = 1)
    {
        echo "\033[{$n}G";
    }

    /**
     * 貌似是从 左上角坐标是 1, 1 而不是0,0
     * Moves the cursor to the specified position (coordinates).
     * If you do not specify a position, the cursor moves to the home position
     * at the upper-left corner of the screen (line 0, column 0).
     * This escape sequence works the same way as the following Cursor Position escape sequence.
     *
     * @param int $line
     * @param int $col
     *
     * Esc[Line;ColumnH
     * Esc[Line;Columnf
     */
    public function cursorPosition($line = 0, $col = 0)
    {
        echo "\033[{$line};{$col}H";
    }

    /**
     * Saves the current cursor position. You can move the cursor to the saved cursor position
     * by using the Restore Cursor Position sequence.
     *
     * Esc[s
     */
    public function saveCursorPosition()
    {
        echo "\033[s";
    }

    /**
     * Returns the cursor to the position stored by the Save Cursor Position sequence.
     *
     * Esc[u
     */
    public function restoreCursorPosition()
    {
        echo "\033[u";
    }



    const TO_END = 0;
    const TO_BEGIN = 1;
    const ENTIRE = 2;

    /**
     * Clears part of the screen.
     * If is 0 (or missing), clear from cursor to end of screen.
     * If is 1, clear from cursor to beginning of the screen.
     * If is 2, clear entire screen (and movescursor to upper left on DOS ANSI.SYS).
     * If is 3, clear entire screen and delete all lines saved in the scrollback buffer
     * (this feature was added for xterm and is supported by other terminal applications).
     * @param $type
     */
    public function eraseScreen($type = self::ENTIRE)
    {
        echo "\033[{$type}J";
    }

    /**
     * Erases part of the line.
     * If is zero (or missing), clear from cursor to the end of the line.
     * If is one, clear from cursor to beginning of the line.
     * If is two, clear entire line. Cursorposition does not change.
     * @param int $type
     */
    public function eraseLine($type = self::ENTIRE)
    {
        echo "\033[{$type}K";
    }


    public function clear()
    {
        $this->eraseScreen();
        $this->cursorPosition();
    }


    /**
     * Set Graphics Mode
     *
     * Calls the graphics functions specified by the following values.
     * These specified functions remain active until the next occurrence of this escape sequence.
     * Graphics mode changes the colors and attributes of text (such as bold and underline)
     * displayed on the screen.
     *
     * Esc[Value;...;Valuem
     *
     * Set Attribute Mode    <ESC>[{attr1};...;{attrn}m
     *
     * Sets multiple display attribute settings. The following lists standard attributes:
     * 0    All attributes off
     * 1    Bright / Bold on
     * 2    Dim
     * 4    Underscore
     * 5    Blink
     * 7    Reverse
     * 8    Hidden / Concealed on
     *
     * Parameters 30 through 47 meet the ISO 6429 standard.
     *
     * Foreground Colours
     * 30    Black
     * 31    Red
     * 32    Green
     * 33    Yellow
     * 34    Blue
     * 35    Magenta
     * 36    Cyan
     * 37    White
     *
     * Background Colours
     * 40    Black
     * 41    Red
     * 42    Green
     * 43    Yellow
     * 44    Blue
     * 45    Magenta
     * 46    Cyan
     * 47    White
     *
     * @param array $attrs
     */
    public function setGraphicsMode(...$attrs)
    {
        if (empty($attrs)) {
            $attrs = [0];
        }
        $attrn = implode(";", $attrs);
        echo "\033[{$attrn}m";
    }

    public function replace($text)
    {
        $numNewLines = substr_count($text, "\n");
//        $this->cursorBackward()
        echo "\033[0G"; // 光标移动到第0列
        echo $text;
        echo "\033[" . $numNewLines ."A"; // 光标向上移动
    }

    public static function put($text, ...$fmt)
    {
        $attrStr = implode(";", $fmt);
        echo "\033[{$attrStr}m{$text}\033[0m";
    }
}