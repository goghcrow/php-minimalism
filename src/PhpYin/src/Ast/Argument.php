<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/15
 * Time: 下午4:27
 */

namespace Minimalism\Scheme\Ast;


use Minimalism\Scheme\Interpreter;

/**
 * Class Argument
 * @package Minimalism\Scheme\Ast
 *
 * 解析函数调用Call所需要的实参
 */
class Argument
{
    /* @var Node[] */
    public $elements;
    /* @var Node[] */
    public $positional;
    /* @var Node[] Map<String, Node> */
    public $keywords;

    /**
     * Argument constructor.
     * @param Node[] $elements
     */
    public function __construct(array $elements)
    {
        $hasName = false;
        $hasKeyword = false;

        for ($size = count($elements), $i = 0; $i < $size; $i++) {
            if ($elements[$i] instanceof Keyword) {
                $hasKeyword = true;
                $i++;
            } else {
                $hasName = true;
            }
        }

        if ($hasName && $hasKeyword) {
            Interpreter::abort("mix positional and keyword arguments not allowed: " . implode(" ", $elements), $elements[0]);
        }

        $this->elements = $elements;
        $this->positional = [];
        $this->keywords = [];

        for ($size = count($elements), $i = 0; $i < $size; $i++) {
            $elem = $elements[$i];
            if ($elem instanceof Keyword) {
                $keyword = $elem;
                $id = $keyword->id;
                //
                $this->positional[] = $keyword->asName();

                if ($i >= ($size - 1)) {
                    Interpreter::abort("missing value for keyword: $keyword", $keyword);
                } else {
                    $value = $elements[$i + 1];
                    if ($value instanceof Keyword) {
                        Interpreter::abort("keywords can't be used as values: $value", $value);
                    } else {
                        if (isset($this->keywords[$id])) {
                            Interpreter::abort("duplicated keyword: $keyword", $keyword);
                        }
                        //
                        $this->keywords[$id] = $value;
                        $i++;
                    }
                }
            } else {
                $this->positional[] = $elem;
            }
        }
    }

    public function __toString()
    {
        return implode(" ", $this->elements);
    }
}