#ifndef PHP_AE_H
#define PHP_AE_H

extern zend_module_entry ae_module_entry;
#define phpext_ae_ptr &ae_module_entry

#ifdef PHP_WIN32
# define PHP_AE_API __declspec(dllexport)
#elif defined(__GNUC__) && __GNUC__ >= 4
# define PHP_AE_API __attribute__ ((visibility("default")))
#else
# define PHP_AE_API
#endif

#ifdef ZTS
# include "TSRM.h"
# define AE_G(v) TSRMG(ae_globals_id, zend_ae_globals *, v)
#else
# define AE_G(v) (ae_globals.v)
#endif

#ifndef PHP_FE_END
# define PHP_FE_END { NULL, NULL, NULL }
#endif

#define PHP_AE_VERSION  "0.0.1-dev"

#define AE_STARTUP_FUNCTION(module)     ZEND_MINIT_FUNCTION(ae_##module)
#define AE_RINIT_FUNCTION(module)       ZEND_RINIT_FUNCTION(ae_##module)
#define AE_STARTUP(module)              ZEND_MODULE_STARTUP_N(ae_##module)(INIT_FUNC_ARGS_PASSTHRU)
#define AE_SHUTDOWN_FUNCTION(module)    ZEND_MSHUTDOWN_FUNCTION(ae_##module)
#define AE_SHUTDOWN(module)             ZEND_MODULE_SHUTDOWN_N(ae_##module)(SHUTDOWN_FUNC_ARGS_PASSTHRU)
#define AE_ACTIVATE_FUNCTION(module)    ZEND_MODULE_ACTIVATE_D(ae_##module)
#define AE_ACTIVATE(module)             ZEND_MODULE_ACTIVATE_N(ae_##module)(INIT_FUNC_ARGS_PASSTHRU)
#define AE_DEACTIVATE_FUNCTION(module)  ZEND_MODULE_DEACTIVATE_D(ae_##module)
#define AE_DEACTIVATE(module)           ZEND_MODULE_DEACTIVATE_N(ae_##module)(SHUTDOWN_FUNC_ARGS_PASSTHRU)

#define AE_INIT_CLASS_ENTRY(ce, name, name_ns, methods) \
    if(AE_G(use_namespace)) {                           \
        INIT_CLASS_ENTRY(ce, name_ns, methods);         \
    } else {                                            \
        INIT_CLASS_ENTRY(ce, name, methods);            \
    }

#define AE_INIT_CLASS_OBJECT(pz, pce)  \
	do {                               \
		Z_TYPE_P((pz)) = IS_OBJECT;    \
		object_init_ex((pz), (pce));   \
		Z_SET_REFCOUNT_P((pz), 1);     \
		Z_SET_ISREF_P((pz));           \
	} while (0)

#if ((PHP_MAJOR_VERSION == 5) && (PHP_MINOR_VERSION < 3))
#define Z_ADDREF_P   ZVAL_ADDREF
#define Z_REFCOUNT_P ZVAL_REFCOUNT
#define Z_DELREF_P   ZVAL_DELREF
#endif

extern PHPAPI void php_var_dump(zval **struc, int level TSRMLS_DC);
extern PHPAPI void php_debug_zval_dump(zval **struc, int level TSRMLS_DC);

/* ini */
ZEND_BEGIN_MODULE_GLOBALS(ae)
    zend_bool debug;
    zend_bool cli;
    zend_bool use_namespace;
ZEND_END_MODULE_GLOBALS(ae)

PHP_MINIT_FUNCTION(ae);
PHP_MSHUTDOWN_FUNCTION(ae);
PHP_RINIT_FUNCTION(ae);
PHP_RSHUTDOWN_FUNCTION(ae);
PHP_MINFO_FUNCTION(ae);

extern ZEND_DECLARE_MODULE_GLOBALS(ae);

/******************************************************************/

#define AE_OBJECT_MAX       10000000
#define AE_OBJECT_INIT      65536

#include "ae/ae.h"
typedef struct {
    aeEventLoop* el;
} ae_t;

void * ae_object_get(zval *object);
void ae_object_set(zval *object, void *ptr);

#endif  /* PHP_AE_H */