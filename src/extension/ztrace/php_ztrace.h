
#ifndef PHP_ZTRACE_H
#define PHP_ZTRACE_H

/* 不支持ZTS */

extern zend_module_entry ztrace_module_entry;

/* 定义模块全局变量 */
ZEND_BEGIN_MODULE_GLOBALS(ztrace)
    long level;
    zend_bool hooked;
ZEND_END_MODULE_GLOBALS(ztrace)

#define ZG(v) (ztrace_globals.v)

PHP_MINIT_FUNCTION(ztrace);

#define PRINT_N_SPACE(n) \
{ \
    for (int i = 0; i < n; ++i) \
        printf("%s", "| "); \
}

#endif  /* PHP_ZTRACE_H */
