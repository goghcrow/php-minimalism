#include "php.h"
#include "aeCoroutine.h"
#include "aeUtil.h"
#include "coroutine/coroutine.h"

extern ae_t * AE;
extern char neterr[1024];

zend_class_entry *ae_coroutine_ce;

static void _coroutine_func(struct schedule *, void *ud);

static void
_coroutine_func(struct schedule *sched, void *ud) {
	ae_event_cb_t* cb = ud;
	call_event_cb(cb, (zval *)cb->data, NULL);
}


PHP_METHOD(ae_coroutine, __construct) { }

PHP_METHOD(ae_coroutine, __destruct) { }

PHP_METHOD(ae_coroutine, open) {
	struct schedule * S = coroutine_open();
	ae_object_set(getThis(), S);
	RETURN_TRUE;
}

PHP_METHOD(ae_coroutine, close) {
	struct schedule* S = ae_object_get(getThis());
	if (S == NULL) {
		php_error_docref(NULL TSRMLS_CC, E_WARNING, "no sched");
		RETURN_FALSE;
	}

	coroutine_close(S);
	ae_object_set(getThis(), NULL);
}

PHP_METHOD(ae_coroutine, new) {
	zend_fcall_info        fci      = empty_fcall_info;
	zend_fcall_info_cache  fcc      = empty_fcall_info_cache;
	ae_event_cb_t*  cb       		= NULL;
	int co_id 						= -1;
	struct schedule* S = ae_object_get(getThis());
	if (S == NULL) {
		php_error_docref(NULL TSRMLS_CC, E_WARNING, "no sched");
		RETURN_FALSE;
	}
	
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "f", &fci, &fcc) == FAILURE) {
	    return;
	}

    cb = new_event_cb(&fci, &fcc);
    cb->data = getThis();
    co_id = coroutine_new(S, _coroutine_func, cb);
	RETURN_LONG(co_id);
}

PHP_METHOD(ae_coroutine, resume) {
	int id;
	struct schedule* S = ae_object_get(getThis());
	if (S == NULL) {
		php_error_docref(NULL TSRMLS_CC, E_WARNING, "no sched");
		RETURN_FALSE;
	}

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "l", &id) == FAILURE) {
	    return;
	}

	coroutine_resume(S, id);
	RETURN_TRUE;
}

PHP_METHOD(ae_coroutine, status) {
	int id;
	int status;
	struct schedule* S = ae_object_get(getThis());
	if (S == NULL) {
		php_error_docref(NULL TSRMLS_CC, E_WARNING, "no sched");
		RETURN_FALSE;
	}

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "l", &id) == FAILURE) {
	    return;
	}

	status = coroutine_status(S, id);
	RETURN_LONG(status);
}

PHP_METHOD(ae_coroutine, running) {
	struct schedule* S = ae_object_get(getThis());
	if (S == NULL) {
		php_error_docref(NULL TSRMLS_CC, E_WARNING, "no sched");
		RETURN_FALSE;
	}

	RETURN_LONG(coroutine_running(S));
}

PHP_METHOD(ae_coroutine, yield) {
	struct schedule* S = ae_object_get(getThis());
	if (S == NULL) {
		php_error_docref(NULL TSRMLS_CC, E_WARNING, "no sched");
		RETURN_FALSE;
	}

	coroutine_yield(S);
	RETURN_TRUE;
}

/** {{{ ae_coroutine_methods
*/
static zend_function_entry ae_coroutine_methods[] = {
    PHP_ME(ae_coroutine, __construct,   NULL,   ZEND_ACC_PUBLIC | ZEND_ACC_CTOR)
    PHP_ME(ae_coroutine, __destruct,    NULL,   ZEND_ACC_PUBLIC | ZEND_ACC_DTOR)
    PHP_ME(ae_coroutine, open,         	NULL,   ZEND_ACC_PUBLIC)
    PHP_ME(ae_coroutine, close,         NULL,   ZEND_ACC_PUBLIC)
    PHP_ME(ae_coroutine, new,           NULL,   ZEND_ACC_PUBLIC)
    PHP_ME(ae_coroutine, resume,        NULL,   ZEND_ACC_PUBLIC)
    PHP_ME(ae_coroutine, status,        NULL,   ZEND_ACC_PUBLIC)
    PHP_ME(ae_coroutine, running,       NULL,   ZEND_ACC_PUBLIC)
    PHP_ME(ae_coroutine, yield,    		NULL,   ZEND_ACC_PUBLIC)
    PHP_FE_END
};
/* }}} */

/** {{{ AE_STARTUP_FUNCTION
*/
AE_STARTUP_FUNCTION(coroutine)
{
    zend_class_entry ce;

    AE_INIT_CLASS_ENTRY(ce, "Ae_Coroutine", "Ae\\Coroutine", ae_coroutine_methods);
    ae_coroutine_ce = zend_register_internal_class_ex(&ce, NULL, NULL TSRMLS_CC);
    ae_coroutine_ce->ce_flags |= ZEND_ACC_FINAL_CLASS;
    
    return SUCCESS;
}
/* }}} */