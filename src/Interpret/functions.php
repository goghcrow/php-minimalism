<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/3/12
 * Time: 下午2:30
 */

namespace Minimalism\A\Interpret;


function abort($exp, $node = null, $desc = null)
{
    if ($exp) {
        return;
    }
    if ($desc !== null) {
        fprintf(STDERR, "$desc\n");
    }
    if ($node !== null) {
        var_dump($node);
    }
    echo "\n";
    debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    exit();
}