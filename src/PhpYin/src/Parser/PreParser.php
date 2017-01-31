<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/15
 * Time: 下午8:49
 */

namespace Minimalism\Scheme\Parser;


use Minimalism\Scheme\Ast\Delimiter;

use Minimalism\Scheme\Ast\Name;

use Minimalism\Scheme\Ast\IntNum;
use Minimalism\Scheme\Ast\FloatNum;
use Minimalism\Scheme\Ast\Str;

use Minimalism\Scheme\Ast\Tuple;

use Minimalism\Scheme\Ast\Keyword;
use Minimalism\Scheme\Ast\Node;
use Minimalism\Scheme\Constants;
use Minimalism\Scheme\Interpreter;


/**
 * Class PreParser
 * @package Minimalism\Scheme\Parser
 * () [] {}
 * --, string, int, float, keyword， 其余全是 name
 */
class PreParser
{
    /* @var string */
    public $file;
    /* @var string */
    public $text;
    /* @var int */
    public $textlen;

    // current offset indicators
    /* @var int */
    public $offset;
    /* @var int */
    public $line;
    /* @var int */
    public $col;

    // all delimeters
    /* @var array [string => true] Set<String> */
    public $delims = [];
    // map open delimeters to their matched closing ones
    /* @var string[] Map<String, String> */
    public $delimMap = [];
    public $delimMap1 = [];

    public function __construct()
    {
        $this->addDelimiterPair(Constants::TUPLE_BEGIN, Constants::TUPLE_END);
        $this->addDelimiterPair(Constants::RECORD_BEGIN, Constants::RECORD_END);
        $this->addDelimiterPair(Constants::VECTOR_BEGIN, Constants::VECTOR_END);

        $this->addDelimiter(Constants::ATTRIBUTE_ACCESS);
    }

    public function loadFile($file)
    {
        $this->file = realpath($file);
        $this->text = file_get_contents($this->file);
        $this->offset = 0;
        $this->line = 0;
        $this->col = 0;

        if ($this->text === false) {
            Interpreter::abort("failed to read file: $file");
            return;
        }
        $this->textlen = strlen($this->text);
    }

    // TODO test
    public function loadStr($str, Node $location)
    {
        $this->file = $location->file;
        $this->text = $str;
        $this->offset = 0;
        $this->line = $location->line;
        $this->col = $location->col;
        $this->textlen = strlen($this->text);
    }

    public function forward()
    {
        if ($this->text[$this->offset] === "\n") {
            $this->line++;
            $this->col = 0;
            $this->offset++;
        } else {
            $this->col++;
            $this->offset++;
        }
    }

    public function addDelimiter($delim)
    {
        $this->delims[$delim] = true;
    }

    public function addDelimiterPair($open, $close)
    {
        $this->delims[$open] = true;
        $this->delims[$close] = true;
        $this->delimMap[$open] = $close;
        $this->delimMap1[$close] = $open;
    }

    public function isDelimiter($char)
    {
        return isset($this->delims[$char]);
    }

    public function isOpen(Node $c)
    {
        if ($c instanceof Delimiter) {
            return isset($this->delimMap[$c->shape]);
        } else {
            return false;
        }
    }

    public function isClose(Node $c)
    {
        if ($c instanceof Delimiter) {
            return isset($this->delimMap1[$c->shape]);
        } else {
            return false;
        }
    }

    public function matchString($open, $close)
    {
        if (isset($this->delimMap[$open]) && $this->delimMap[$open] === $close) {
            return true;
        } else {
            return false;
        }
    }

    public function matchDelim(Node $open, Node $close)
    {
        return ($open instanceof Delimiter &&
            $close instanceof Delimiter &&
            $this->matchString($open->shape, $close->shape));
    }

    private function isWhitespace($char)
    {
        // http://php.net/manual/en/intlchar.iswhitespace.php
        if (class_exists("\\IntlChar")) {
            return \IntlChar::isWhitespace($char);
        } else {
            return strpos(" \r\n\t\v", $char) !== false;
        }
    }

    /**
     * lexer
     *
     * @return Node a token or null if file ends
     */
    private function nextToken()
    {
        $seenComment = true;
        while ($seenComment) {
            $seenComment = false;

            // skip spaces
            while ($this->offset < $this->textlen &&
                $this->isWhitespace($this->text[$this->offset]))
            {
                $this->forward();
            }

            // comments
            if ($this->offset + strlen(Constants::LINE_COMMENT) <= $this->textlen &&
                    substr($this->text, $this->offset, strlen(Constants::LINE_COMMENT)) === Constants::LINE_COMMENT) {
                while ($this->offset < $this->textlen && $this->text[$this->offset] !== "\n") {
                    $this->forward();
                }
                if ($this->offset < $this->textlen) {
                    $this->forward(); // skip "\n"
                }

                // 处理连续多行注释
                $seenComment = true;
            }
        }

        // end of file
        if ($this->offset >= $this->textlen) {
            return null;
        }



        $cur = $this->text[$this->offset];

        // delimiters
        if ($this->isDelimiter($cur)) {
            $delim = new Delimiter($cur, $this->file, $this->offset, $this->offset + 1, $this->line, $this->col);
            $this->forward();
            return $delim;
        }

        // string
        if ($this->text[$this->offset] === '"' && ($this->offset === 0 || $this->text[$this->offset - 1] !== '\\')) {
            $start = $this->offset;
            $startLine = $this->line;
            $startCol = $this->col;
            $this->forward();   // skip "

            while ($this->offset < $this->textlen &&
                !($this->text[$this->offset] === '"' && $this->text[$this->offset - 1] !== '\\'))
            {
                if ($this->text[$this->offset] === "\n") {
                    Interpreter::abort("$this->file:$startLine:$startCol: runaway string");
                    return null;
                }
                $this->forward();
            }

            if ($this->offset >= $this->textlen) {
                Interpreter::abort("$this->file:$startLine:$startCol: runaway string");
                return null;
            }

            $this->forward(); // skip "
            $end = $this->offset;

            $content = substr($this->text, $start + 1, $end - $start - 2);
            return new Str($content, $this->file, $start, $end, $startLine, $startCol);
        }


        // find consecutive token, 连续字符token
        $start = $this->offset;
        $startLine = $this->line;
        $startCol = $this->col;

        if (ctype_digit($this->text[$start]) ||
            (($this->text[$start] === '+' || $this->text[$start] === '-') && ctype_digit($this->text[$start + 1])))
        {
            // number
            while ($this->offset < $this->textlen &&
                !$this->isWhitespace($cur) &&
                !($this->isDelimiter($cur) && $cur !== '.')) // 排除小数点
            {
                $this->forward();
                if ($this->offset < $this->textlen) {
                    $cur = $this->text[$this->offset];
                }
            }

            $content = substr($this->text, $start, $this->offset - $start);

            $intNum = IntNum::parse($content, $this->file, $start, $this->offset, $startLine, $startCol);
            if ($intNum !== null) {
                return $intNum;
            } else {
                $floatNum = FloatNum::parse($content, $this->file, $start, $this->offset, $startLine, $startCol);
                if ($floatNum !== null) {
                    return $floatNum;
                } else {
                    Interpreter::abort("$this->file:$startLine:$startCol: incorrect number format: $content");
                    return null;
                }
            }
        } else {

            while ($this->offset < $this->textlen &&
                !$this->isWhitespace($cur) &&
                !$this->isDelimiter($cur))
            {
                $this->forward();
                if ($this->offset < $this->textlen) {
                    $cur = $this->text[$this->offset];
                }
            }

            $content = substr($this->text, $start, $this->offset - $start);

            if (preg_match("#:\\w.*#", $content, $matches)) {
                return new Keyword(substr($content, 1), $this->file, $start, $this->offset, $startLine, $startCol);
            } else {
                return new Name($content, $this->file, $start, $this->offset, $startLine, $startCol);
            }
        }
    }

    /**
     * parser
     * @param int $depth
     * @return Node a Node or null if file ends
     */
    public function nextNode($depth)
    {
        $begin = $this->nextToken();

        // end of file
        if ($begin === null) {
            return null;
        }

        if ($depth == 0 && $this->isClose($begin)) {
            Interpreter::abort("unmatched closing delimeter: begin", $begin);
            return null;
        } else if ($this->isOpen($begin)) {   // try to get matched (...)
            /* @var Node[] */
            $elements = [];
            $iter = $this->nextNode($depth + 1);

            while ($iter === null || !$this->matchDelim($begin, $iter)) {
                if ($iter == null) {
                    Interpreter::abort("unclosed delimeter: $begin", $begin);
                    return null;
                } else if ($this->isClose($iter)) {
                    Interpreter::abort("unmatched closing delimeter: $iter", $iter);
                    return null;
                } else {
                    $elements[] = $iter;
                    $iter = $this->nextNode($depth + 1);
                }
            }
            return new Tuple($elements, $begin, $iter, $begin->file, $begin->start, $iter->end, $begin->line, $begin->col);
        } else {
            return $begin;
        }
    }

    /**
     * @return Node
     */
    public function nextSexp()
    {
        return $this->nextNode(0);
    }

    /**
     * parse file into a Node
     * @return Tuple
     */
    public function parse()
    {
        /* @var Node[]  List<Node> */
        $elements = [];

        // !!! 程序本身是seq，构成一个Block
        // synthetic block keyword
        $elements[] = $this->genName(Constants::SEQ_KEYWORD);

        /* @var $s Node */
        $s = $this->nextSexp();
        while ($s !== null) {
            $elements[] = $s;
            $s = $this->nextSexp();
        }

        return new Tuple($elements, $this->genName(Constants::TUPLE_BEGIN), $this->genName(Constants::TUPLE_END),
            $this->file, 0, $this->textlen, 0, 0);
    }

    /**
     * @param $id
     * @return Name
     */
    public function genName($id) {
        return new Name($id, $this->file, 0, 0, 0, 0);
    }
}