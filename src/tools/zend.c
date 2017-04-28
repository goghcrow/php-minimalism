/*
7.1.3 移除ZTS与WIN部分
php-7.1.3/Zend/zend.c

php-7.1.3/Zend/zend_dtrace.c
*/
int zend_startup(zend_utility_functions *utility_functions, char **extensions) /* {{{ */
{
	extern zend_ini_scanner_globals ini_scanner_globals;
	extern zend_php_scanner_globals language_scanner_globals;

	start_memory_manager();

	virtual_cwd_startup(); /* Could use shutdown to free the main cwd but it would just slow it down for CGI */

	zend_startup_strtod();
	zend_startup_extensions_mechanism();

	/* Set up utility functions and values */
	zend_error_cb = utility_functions->error_function;
	zend_printf = utility_functions->printf_function;
	zend_write = (zend_write_func_t) utility_functions->write_function;
	zend_fopen = utility_functions->fopen_function;
	if (!zend_fopen) {
		zend_fopen = zend_fopen_wrapper;
	}
	zend_stream_open_function = utility_functions->stream_open_function;
	zend_message_dispatcher_p = utility_functions->message_handler;
	zend_get_configuration_directive_p = utility_functions->get_configuration_directive;
	zend_ticks_function = utility_functions->ticks_function;
	zend_on_timeout = utility_functions->on_timeout;
	zend_vspprintf = utility_functions->vspprintf_function;
	zend_vstrpprintf = utility_functions->vstrpprintf_function;
	zend_getenv = utility_functions->getenv_function;
	zend_resolve_path = utility_functions->resolve_path_function;

	zend_interrupt_function = NULL;

#if HAVE_DTRACE
    /* build with dtrace support */
	{
		char *tmp = getenv("USE_ZEND_DTRACE");

		if (tmp && zend_atoi(tmp, 0)) {
			zend_dtrace_enabled = 1;
			zend_compile_file = dtrace_compile_file;
			zend_execute_ex = dtrace_execute_ex;
			zend_execute_internal = dtrace_execute_internal;
		} else {
			zend_compile_file = compile_file;
			zend_execute_ex = execute_ex;
			zend_execute_internal = NULL;
		}
	}
#else
	zend_compile_file = compile_file;
	zend_execute_ex = execute_ex;
	zend_execute_internal = NULL;
#endif /* HAVE_SYS_SDT_H */
	zend_compile_string = compile_string;
	zend_throw_exception_hook = NULL;

	/* Set up the default garbage collection implementation. */
	gc_collect_cycles = zend_gc_collect_cycles;

	zend_init_opcodes_handlers();

	/* set up version */
	zend_version_info = strdup(ZEND_CORE_VERSION_INFO);
	zend_version_info_length = sizeof(ZEND_CORE_VERSION_INFO) - 1;

	GLOBAL_FUNCTION_TABLE = (HashTable *) malloc(sizeof(HashTable));
	GLOBAL_CLASS_TABLE = (HashTable *) malloc(sizeof(HashTable));
	GLOBAL_AUTO_GLOBALS_TABLE = (HashTable *) malloc(sizeof(HashTable));
	GLOBAL_CONSTANTS_TABLE = (HashTable *) malloc(sizeof(HashTable));

	zend_hash_init_ex(GLOBAL_FUNCTION_TABLE, 1024, NULL, ZEND_FUNCTION_DTOR, 1, 0);
	zend_hash_init_ex(GLOBAL_CLASS_TABLE, 64, NULL, ZEND_CLASS_DTOR, 1, 0);
	zend_hash_init_ex(GLOBAL_AUTO_GLOBALS_TABLE, 8, NULL, auto_global_dtor, 1, 0);
	zend_hash_init_ex(GLOBAL_CONSTANTS_TABLE, 128, NULL, ZEND_CONSTANT_DTOR, 1, 0);

	zend_hash_init_ex(&module_registry, 32, NULL, module_destructor_zval, 1, 0);
	zend_init_rsrc_list_dtors();

	ini_scanner_globals_ctor(&ini_scanner_globals);
	php_scanner_globals_ctor(&language_scanner_globals);
	zend_set_default_compile_time_values();
	EG(error_reporting) = E_ALL & ~E_NOTICE;

	zend_interned_strings_init();
	zend_startup_builtin_functions();
	zend_register_standard_constants();
	zend_register_auto_global(zend_string_init("GLOBALS", sizeof("GLOBALS") - 1, 1), 1, php_auto_globals_create_globals);

	zend_ini_startup();

	return SUCCESS;
}
/* }}} */