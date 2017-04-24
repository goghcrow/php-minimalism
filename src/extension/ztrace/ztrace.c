
#include <stdio.h>

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "zend_extensions.h"
#include "zend.h"
#include "zend_API.h"
#include "zend_exceptions.h"

#include "php_ztrace.h"

/* TODO */
/* -trace=funa,funb,func 过滤器 */
/* 参数返回值 */

/* 参考 php-7.1.3/Zend/zend_dtrace.c 实现 */

static zend_op_array *(*ori_compile_string)(zval *source_string, char *filename);
static zend_op_array *(*ori_compile_file)(zend_file_handle *file_handle, int type);
static void (*ori_execute_ex)(zend_execute_data *execute_data);
static void (*ori_execute_internal)(zend_execute_data *execute_data, zval *return_value);
static void (*ori_throw_exception_hook)(zval *ex);
static int  (*ori_gc_collect_cycles)(void);

static void php_ztrace_init_globals(zend_ztrace_globals *zg);
static void begin_hook();
static void end_hook();

ZEND_API zend_op_array *ztrace_compile_string(zval *source_string, char *filename); /* for eval() */
ZEND_API zend_op_array *ztrace_compile_file(zend_file_handle *file_handle, int type);
ZEND_API void ztrace_execute_ex(zend_execute_data *execute_data);
ZEND_API void ztrace_execute_internal(zend_execute_data *execute_data, zval *return_value);
ZEND_API void ztrace_throw_exception_hook(zval *exception);
ZEND_API int  ztrace_gc_collect_cycles(void);

/* 声明模块全局变量 */
ZEND_DECLARE_MODULE_GLOBALS(ztrace)

zend_module_entry ztrace_module_entry = {
    STANDARD_MODULE_HEADER,
    "ztrace",
    NULL,
    PHP_MINIT(ztrace),
    NULL,
    NULL,
    NULL,
    NULL,
    "0.0.1",
    STANDARD_MODULE_PROPERTIES
};

#ifdef COMPILE_DL_ZTRACE
	ZEND_GET_MODULE(ztrace)
#endif

static inline const char *ztrace_get_executed_filename(void)
{
	zend_execute_data *ex = EG(current_execute_data);

	while (ex && (!ex->func || !ZEND_USER_CODE(ex->func->type))) {
		ex = ex->prev_execute_data;
	}
	if (ex) {
		return ZSTR_VAL(ex->func->op_array.filename);
	} else {
		return zend_get_executed_filename();
	}
}

ZEND_API zend_op_array *ztrace_compile_string(zval *source_string, char *filename)
{
	zend_op_array *res;

	PRINT_N_SPACE(ZG(level));
	printf("COMPILE_STRING_ENTRY: filename=%s\n", filename);	
	
	PRINT_N_SPACE(ZG(level));
	puts("<<<");
	zend_print_zval_r(source_string, 0);
	puts("");
	puts("EOL");

	res = ori_compile_string(source_string, filename);
	
	PRINT_N_SPACE(ZG(level));
	printf("COMPILE_STRING_RETURN: filename=%s\n", filename);	

	return res;
}

ZEND_API zend_op_array *ztrace_compile_file(zend_file_handle *file_handle, int type)
{
	zend_op_array *res;

	if (file_handle->opened_path) {
		PRINT_N_SPACE(ZG(level));
    	printf("COMPILE_FILE_ENTRY: opened_path=%s, filename=%s\n", ZSTR_VAL(file_handle->opened_path), (char *)file_handle->filename);	
	} else {
		PRINT_N_SPACE(ZG(level));
		printf("COMPILE_FILE_ENTRY: filename=%s\n", (char *)file_handle->filename);	
	}
	
    res = ori_compile_file(file_handle, type);
	
	if (file_handle->opened_path) {
		PRINT_N_SPACE(ZG(level));
    	printf("COMPILE_FILE_RETURN: opened_path=%s, filename=%s\n", ZSTR_VAL(file_handle->opened_path), (char *)file_handle->filename);
	} else {
		PRINT_N_SPACE(ZG(level));
		printf("COMPILE_FILE_RETURN: filename=%s\n", (char *)file_handle->filename);
	}

	return res;
}

ZEND_API void ztrace_execute_ex(zend_execute_data *execute_data)
{
	int lineno;
	const char *scope, *filename, *funcname, *classname;
	scope = filename = funcname = classname = NULL;

	filename = ztrace_get_executed_filename();
	lineno = zend_get_executed_lineno();

	classname = get_active_class_name(&scope);
	funcname = get_active_function_name();

	ZG(level)++;
    /* printf("=> EXECUTE_ENTRY: %s#%d\n", (char *)filename, lineno); */

	/* FUNCTION_ENTRY */
    if (funcname != NULL) {
		if (classname) {
			PRINT_N_SPACE(ZG(level));
        	printf("┌- call %s%s%s() %s#%d\n", (char *)classname, (char *)scope, (char *)funcname, (char *)filename, lineno);
			/* printf("┌- call %s%s%s()\n", (char *)classname, (char *)scope, (char *)funcname); */
		} else {
			PRINT_N_SPACE(ZG(level));
			printf("┌- call %s() %s#%d\n", (char *)funcname, (char *)filename, lineno);
			/* printf("┌- call %s()\n", (char *)funcname); */
		}
    }

	/* TODO: 收集参数返回值信息 */

	ori_execute_ex(execute_data);

	/* FUNCTION_RETURN */
	if (funcname != NULL) {
		if (classname) {
			PRINT_N_SPACE(ZG(level));
        	printf("└- return %s%s%s %s#%d\n", (char *)classname, (char *)scope, (char *)funcname, (char *)filename, lineno);
			/* printf("└- return %s%s%s\n", (char *)classname, (char *)scope, (char *)funcname); */
		} else {
			PRINT_N_SPACE(ZG(level));
			printf("└- return %s %s#%d\n", (char *)funcname, (char *)filename, lineno);
			/* printf("└- return %s\n", (char *)funcname); */
		}
	}

    /* printf("<= EXECUTE_RETURN: %s#%d\n", filename, lineno); */
	ZG(level)--;
}

ZEND_API void ztrace_execute_internal(zend_execute_data *execute_data, zval *return_value)
{
	int lineno;
	const char *scope, *filename, *funcname, *classname;
	scope = filename = funcname = classname = NULL;

	filename = ztrace_get_executed_filename();
	lineno = zend_get_executed_lineno();

	classname = get_active_class_name(&scope);
	funcname = get_active_function_name();

	ZG(level)++;
	/* printf("=> INTERNAL_EXECUTE_ENTRY: %s#%d\n", (char *)filename, lineno); */

	/* FUNCTION_ENTRY */
    if (funcname != NULL) {
		if (classname) {
			PRINT_N_SPACE(ZG(level));
        	printf("┌- call %s%s%s() %s#%d\n", (char *)classname, (char *)scope, (char *)funcname, (char *)filename, lineno);
			/* printf("┌- call %s%s%s()\n", (char *)classname, (char *)scope, (char *)funcname); */
		} else {
			PRINT_N_SPACE(ZG(level));
			printf("┌- call %s() %s#%d\n", (char *)funcname, (char *)filename, lineno);
			/* printf("┌- call %s()\n", (char *)funcname); */
		}
    }

	/* TODO: 收集参数返回值信息 */

	if (ori_execute_internal) {
		ori_execute_internal(execute_data, return_value);
	} else {
		execute_internal(execute_data, return_value);
	}


	/* FUNCTION_RETURN */
	if (funcname != NULL) {
		if (classname) {
			PRINT_N_SPACE(ZG(level));
        	printf("└- return %s%s%s %s#%d\n", (char *)classname, (char *)scope, (char *)funcname, (char *)filename, lineno);
			/* printf("└- return %s%s%s\n", (char *)classname, (char *)scope, (char *)funcname); */
		} else {
			PRINT_N_SPACE(ZG(level));
			printf("└- return %s %s#%d\n", (char *)funcname, (char *)filename, lineno);
			/* printf("└- return %s\n", (char *)funcname); */
		}
	}

    /* printf("<= INTERNAL_EXECUTE_RETURN: %s#%d\n", (char *)filename, lineno); */
	ZG(level)--;


	/*
	int lineno;
	const char *filename;
	
	filename = ztrace_get_executed_filename();
	lineno = zend_get_executed_lineno();

    ZG(level)++;
	if (ori_execute_internal) {
		ori_execute_internal(execute_data, return_value);
	} else {
		execute_internal(execute_data, return_value);
	}
	ZG(level)--;
	*/	
}

ZEND_API void ztrace_throw_exception_hook(zval *exception)
{
	if (exception != NULL) {
		PRINT_N_SPACE(ZG(level));
		printf("| <ex> %s\n", (char *)ZSTR_VAL(Z_OBJ_P(exception)->ce->name));
		/* zend_print_zval_r(exception, 0); */
	}

	if (ori_throw_exception_hook) {
		ori_throw_exception_hook(exception);
	}
}

ZEND_API int  ztrace_gc_collect_cycles(void)
{
	int count;
	count = ori_gc_collect_cycles();
	PRINT_N_SPACE(ZG(level));
	printf("| <gc> %d\n", count);
	return count;
}

static void php_ztrace_init_globals(zend_ztrace_globals *zg)
{
	zg->level = -1;
}

/* zm_startup_ztrace */
PHP_MINIT_FUNCTION(ztrace)
{
	begin_hook();
    return SUCCESS;
}

__attribute__((constructor))
static void begin_hook()
{
	static char hooked = 0;
	if (hooked) {
	/* 
		作为扩展加载时, 晚于zend_startup执行, 且在PHP_MINIT_FUNCTION也会被调用两次
		作为动态库运行时注入时, 只会被调用一次
 	*/
		return;
	}

	/* 初始化模块全局变量 */
	ZEND_INIT_MODULE_GLOBALS(ztrace, php_ztrace_init_globals, NULL);

	ori_compile_string = zend_compile_string;
    ori_compile_file = zend_compile_file;
    ori_execute_ex = zend_execute_ex;
    ori_execute_internal = zend_execute_internal;
    ori_throw_exception_hook = zend_throw_exception_hook;
	ori_gc_collect_cycles = gc_collect_cycles;

	zend_compile_string = ztrace_compile_string;
    zend_compile_file = ztrace_compile_file;
    zend_execute_ex = ztrace_execute_ex;
    zend_execute_internal = ztrace_execute_internal;
    zend_throw_exception_hook = ztrace_throw_exception_hook;
	gc_collect_cycles = ztrace_gc_collect_cycles;

	hooked = 1;
}

__attribute__((destructor))
static void end_hook()
{
	zend_compile_string = ori_compile_string; /* compile_string */
    zend_compile_file = ori_compile_file; /* compile_file */
    zend_execute_ex = ori_execute_ex; /* execute_ex */
    zend_execute_internal = ori_execute_internal; /* NULL */
	zend_throw_exception_hook = ori_throw_exception_hook; /* NULL */
	gc_collect_cycles = ori_gc_collect_cycles; /* zend_gc_collect_cycles */
}