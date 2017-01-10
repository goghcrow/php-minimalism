#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_ini.h"
#include <ext/standard/php_string.h>
#include <ext/standard/info.h>      /* for php_info_print_* */
#include <main/SAPI.h>              /* for sapi_module_struct */
#include <php_network.h>
#include <ext/sockets/php_sockets.h>/* php_socket */
#include <php_streams.h>            

#include "php_ae.h"
#include "aeTcpServer.h"
#include "aeEventLoop.h"
#include "aeCoroutine.h"

ZEND_DECLARE_MODULE_GLOBALS(ae);

ae_t * AE;
char neterr[1024];

extern sapi_module_struct sapi_module;

/* TODO: NOT SUPPORT ZTS*/
static struct {
    void ** array;
    uint32_t size;
} ae_objects;

static void ae_init();
static void ae_free();
static void ae_object_init();

char neterr[1024];

/* {{{ ae_functions[]
 */
const zend_function_entry ae_functions[] = {

    PHP_FE_END
};
/* }}} */

/* {{{ PHP_INI
 */
PHP_INI_BEGIN()
    STD_PHP_INI_ENTRY("ae.debug",  "Off", PHP_INI_ALL, OnUpdateBool, debug, zend_ae_globals, ae_globals)
PHP_INI_END()
/* }}} */


/** {{{ PHP_GINIT_FUNCTION
*/
PHP_GINIT_FUNCTION(ae)
{
    /** 初始化全局变量 */
}
/* }}} */

/** {{{ PHP_GSHUTDOWN_FUNCTION
*/
PHP_GSHUTDOWN_FUNCTION(ae)
{
    /** 清除全局变量 */
}

/** {{{ PHP_MINIT_FUNCTION
*/
PHP_MINIT_FUNCTION(ae)
{
    REGISTER_INI_ENTRIES();
    REGISTER_STRINGL_CONSTANT("AE_VERSION", PHP_AE_VERSION, sizeof(PHP_AE_VERSION)-1, CONST_CS|CONST_PERSISTENT);

    if (strcasecmp("cli", sapi_module.name) == 0)
    {
        AE_G(cli) = 1;
    }
    AE_G(debug) = 1;
    AE_G(use_namespace) = 1;

    /* startup components */
    AE_STARTUP(eventloop);
    AE_STARTUP(tcpserver);
    AE_STARTUP(coroutine);

    ae_init();

    return SUCCESS;
}
/* }}} */

/** {{{ PHP_MSHUTDOWN_FUNCTION
*/
PHP_MSHUTDOWN_FUNCTION(ae)
{
    UNREGISTER_INI_ENTRIES();

    // AE_SHUTDOWN(xxx);

    return SUCCESS;
}
/* }}} */

/** {{{ PHP_RINIT_FUNCTION
*/
PHP_RINIT_FUNCTION(ae)
{
    return SUCCESS;
}
/* }}} */

/** {{{ PHP_RSHUTDOWN_FUNCTION
*/
PHP_RSHUTDOWN_FUNCTION(ae)
{
    return SUCCESS;
}
/* }}} */

/** {{{ PHP_MINFO_FUNCTION
*/
PHP_MINFO_FUNCTION(ae)
{
    php_info_print_table_start();
    php_info_print_table_header(2, "ae support", "enabled");
    php_info_print_table_row(2, "Version", PHP_AE_VERSION);
    php_info_print_table_end();

    DISPLAY_INI_ENTRIES();
}
/* }}} */

/** {{{ DL support
 */
#ifdef COMPILE_DL_AE
ZEND_GET_MODULE(ae)
#endif
/* }}} */

/** {{{ module depends
 */
#if ZEND_MODULE_API_NO >= 20050922
zend_module_dep ae_deps[] = {
    ZEND_MOD_REQUIRED("spl")
    ZEND_MOD_REQUIRED("pcre")
    {NULL, NULL, NULL}
};
#endif
/* }}} */

/* {{{ ae_module_entry
 */
zend_module_entry ae_module_entry = {
#if ZEND_MODULE_API_NO >= 20050922
    STANDARD_MODULE_HEADER_EX, NULL,
    ae_deps,
#else
    STANDARD_MODULE_HEADER,
#endif
    "ae",
    ae_functions,
    PHP_MINIT(ae),
    PHP_MSHUTDOWN(ae),
    PHP_RINIT(ae),
    PHP_RSHUTDOWN(ae),
    PHP_MINFO(ae),
#if ZEND_MODULE_API_NO >= 20010901
    PHP_AE_VERSION,
#endif
    PHP_MODULE_GLOBALS(ae),
    PHP_GINIT(ae),
    NULL,
    NULL,
    STANDARD_MODULE_PROPERTIES_EX
};




static void 
ae_init() {
    AE = emalloc(sizeof(ae_t)); 
    AE->el = newAeEventloop();

    ae_object_init();
}

static void
ae_free() {
    releaseAeEventloop(AE->el);
    efree(AE);
    AE = NULL;
}



static void
ae_object_init() {
    ae_objects.size = AE_OBJECT_INIT;
    ae_objects.array = calloc(AE_OBJECT_INIT, sizeof(void *));
}

void *
ae_object_get(zval *object) {
    zend_object_handle handle = Z_OBJ_HANDLE_P(object);
    assert(handle < ae_objects.size);
    return ae_objects.array[handle];
}

void 
ae_object_set(zval *object, void *ptr) {
    zend_object_handle handle = Z_OBJ_HANDLE_P(object);
    assert(handle < AE_OBJECT_MAX);
    if (handle >= ae_objects.size)
    {
        uint32_t old_size = ae_objects.size;
        uint32_t new_size = old_size * 2;

        void *old_ptr = ae_objects.array;
        void *new_ptr = NULL;
        
        if (new_size > AE_OBJECT_MAX)
        {
            
            new_size = AE_OBJECT_MAX;
        }
        new_ptr = realloc(old_ptr, sizeof(void*) * new_size);
        if (!new_ptr)
        {
            return;
        }
        bzero(new_ptr + (old_size * sizeof(void*)), (new_size - old_size) * sizeof(void*));
        ae_objects.array = new_ptr;
        ae_objects.size = new_size;
    }
    ae_objects.array[handle] = ptr;
}