#ifndef AE_UTILS_H
#define AE_UTILS_H

#include "php.h" /* zend_fcall_info */

/* Anti-warning macro... */
#define UNUSED(V) ((void) V)

/*
#include "zend_variables.h"
#include <php_streams.h>
#include <ext/sockets/php_sockets.h>
#include <php_network.h>
*/

#ifdef ZTS
# include "TSRM.h"
# define AE_G(v) TSRMG(ae_globals_id, zend_ae_globals *, v)
# define TSRMLS_FETCH_FROM_CTX(ctx) void ***tsrm_ls = (void ***) ctx
# define TSRMLS_SET_CTX(ctx)        ctx = (void ***) tsrm_ls
#else
# define AE_G(v) (ae_globals.v)
# define TSRMLS_FETCH_FROM_CTX(ctx)
# define TSRMLS_SET_CTX(ctx)
#endif

#if defined(PHP_WIN32)
#if defined(ZTS)
#  define AE_TSRMLS_FETCH_FROM_CTX(ctx) tsrm_ls = (void ***)ctx
#  define AE_TSRM_DECL void ***tsrm_ls;
# else
#  define AE_TSRMLS_FETCH_FROM_CTX(ctx)
#  define AE_TSRM_DECL
# endif
#else
# define AE_TSRMLS_FETCH_FROM_CTX(ctx) TSRMLS_FETCH_FROM_CTX(ctx)
# define AE_TSRM_DECL
#endif

/* Thread context. With it we are getting rid of need
 * to call the heavy TSRMLS_FETCH() */
#ifdef ZTS
# define AE_COMMON_THREAD_CTX void ***thread_ctx;
#else
# define AE_COMMON_THREAD_CTX
#endif

#if PHP_VERSION_ID >= 50300
# define AE_FCI_ADDREF(pfci)              \
{                                         \
    Z_ADDREF_P(pfci->function_name);      \
    if (pfci->object_ptr) {               \
        Z_ADDREF_P(pfci->object_ptr);     \
    }                                     \
}
# define AE_FCI_DELREF(pfci)              \
{                                         \
    zval_ptr_dtor(&pfci->function_name);  \
    if (pfci->object_ptr) {               \
        zval_ptr_dtor(&pfci->object_ptr); \
    }                                     \
}
#else
# define AE_FCI_ADDREF(pfci) Z_ADDREF_P(pfci_dst->function_name)
# define AE_FCI_DELREF(pfci) zval_ptr_dtor(&pfci->function_name)
#endif

#define AE_COPY_FCALL_INFO(pfci_dst, pfcc_dst, pfci, pfcc)                                       \
    if (ZEND_FCI_INITIALIZED(*pfci)) {                                                           \
        pfci_dst = (zend_fcall_info *) safe_emalloc(1, sizeof(zend_fcall_info), 0);              \
        pfcc_dst = (zend_fcall_info_cache *) safe_emalloc(1, sizeof(zend_fcall_info_cache), 0);  \
                                                                                                 \
        memcpy(pfci_dst, pfci, sizeof(zend_fcall_info));                                         \
        memcpy(pfcc_dst, pfcc, sizeof(zend_fcall_info_cache));                                   \
                                                                                                 \
        AE_FCI_ADDREF(pfci_dst);                                                                 \
    } else {                                                                                     \
        pfci_dst = NULL;                                                                         \
        pfcc_dst = NULL;                                                                         \
    }                                                                                            \

#define AE_FREE_FCALL_INFO(pfci, pfcc)                                                           \
    if (pfci && pfcc) {                                                                          \
        efree(pfcc);                                                                             \
        pfcc = NULL;                                                                             \
                                                                                                 \
        if (ZEND_FCI_INITIALIZED(*pfci)) {                                                       \
            AE_FCI_DELREF(pfci);                                                                 \
        }                                                                                        \
        efree(pfci);                                                                             \
        pfci = NULL;                                                                             \
    }                                                                                            \

typedef struct {
    void*                  data;
    zend_fcall_info*       fci;
    zend_fcall_info_cache* fcc;

    AE_COMMON_THREAD_CTX
} ae_event_cb_t;

ae_event_cb_t* new_event_cb(const zend_fcall_info *fci, const zend_fcall_info_cache *fcc TSRMLS_DC);
void call_event_cb(ae_event_cb_t* cb, zval* zval_arg1, zval* zval_arg2);


#endif  /* AE_UTILS_H */ 