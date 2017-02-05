<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/5
 * Time: 下午10:18
 */

namespace Minimalism\Async\Interpret;


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

    const SEQ_KEYWORD = "seq";
    const FUN_KEYWORD = "fun";
    const IF_KEYWORD = "if";
    const DEF_KEYWORD = "define";
    const QUOTE_KEYWORD = "quote";
    const CALLCC_KEYWORD = "call/cc";


//    const RETURN_ARROW = "->";
//    const ASSIGN_KEYWORD = "set!";
//    const RECORD_KEYWORD = "record";
//    const DECLARE_KEYWORD = "declare";
}