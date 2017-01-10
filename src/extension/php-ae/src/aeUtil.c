#include "php.h"
#include <php_streams.h>
#include <php_network.h>            /* for php_socket* */
#include "ext/sockets/php_sockets.h"
#include "aeUtil.h"


ae_event_cb_t* 
new_event_cb(const zend_fcall_info *fci, const zend_fcall_info_cache *fcc TSRMLS_DC) {
    ae_event_cb_t *cb = emalloc(sizeof(ae_event_cb_t));
    bzero(cb, sizeof(ae_event_cb_t));
    AE_COPY_FCALL_INFO(cb->fci, cb->fcc, fci, fcc);
    TSRMLS_SET_CTX(cb->thread_ctx);
    return cb;
}

/* {{{ call_event_cb */
void 
call_event_cb(ae_event_cb_t* cb, zval* zval_arg1, zval* zval_arg2) {
    zend_fcall_info*       pfci;
    zend_fcall_info_cache* pfcc;
    php_socket* php_sock;
    zval** args[2];
    zval*  retval_ptr = NULL;
    AE_TSRM_DECL

    pfci = cb->fci;
    pfcc = cb->fcc;

    AE_TSRMLS_FETCH_FROM_CTX(cb->thread_ctx);

    args[0] = &zval_arg1;
    args[1] = &zval_arg2;

    pfci->params         = args;
    pfci->retval_ptr_ptr = &retval_ptr;
    if (zval_arg2 == NULL) {
        pfci->param_count = 1;
    } else {
        pfci->param_count = 2;        
    }
    pfci->no_separation  = 1;

    if (zend_call_function(pfci, pfcc TSRMLS_CC) == SUCCESS && retval_ptr) {
        zval_ptr_dtor(&retval_ptr);
    } else {
        php_error_docref(NULL TSRMLS_CC, E_WARNING, "An error occurred while invoking event callback");
        /*if (EG(exception)) {
            zend_throw_exception_object(EG(exception) TSRMLS_CC);
        } else {
            php_error_docref(NULL TSRMLS_CC, E_WARNING, "An error occurred while invoking event callback");
        }*/
    }
}
/* }}} */
