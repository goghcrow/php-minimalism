<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/13
 * Time: 上午1:30
 */

namespace Minimalism\Scheme;


class Constants
{
    const LINE_COMMENT = "--";

    const TUPLE_BEGIN = "(";
    const TUPLE_END = ")";

    const RECORD_BEGIN = "{";
    const RECORD_END = "}";

    const VECTOR_BEGIN = "[";
    const VECTOR_END = "]";

    const ATTRIBUTE_ACCESS = ".";
    const RETURN_ARROW = "->";

    // keywords
    const SEQ_KEYWORD = "seq";
    const FUN_KEYWORD = "fun";
    const IF_KEYWORD = "if";
    const DEF_KEYWORD = "define";
    const ASSIGN_KEYWORD = "set!";
    const RECORD_KEYWORD = "record";
    const DECLARE_KEYWORD = "declare";
    const UNION_KEYWORD = "U";

    const QUOTE_KEYWORD = "quote";
}