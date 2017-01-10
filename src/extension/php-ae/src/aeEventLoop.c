#include "php.h"

#include "aeEventLoop.h"
#include "aeUtil.h"

#include "ae/config.h"
#include "ae/ae.h"
#include "ae/anet.h"

#include <php_network.h>
#include <php_streams.h>
#include "zend_exceptions.h"

#ifndef PHP_WIN32
# include <fcntl.h>
#endif

extern ae_t * AE;
extern char neterr[1024];

zend_class_entry *ae_eventloop_ce;

static php_socket* fd_to_php_sock(php_socket_t fd);
static php_socket_t zval_to_fd(zval **ppfd TSRMLS_DC);
static zend_always_inline void aeEventCallback(aeEventLoop *el, int fd, void *privdata, int mask);
static zend_always_inline int aeTimerCallback(aeEventLoop *eventLoop, long long id, void *clientData);
static zend_always_inline void aeTimerFinalizer(struct aeEventLoop *eventLoop, void *clientData);

// #ifdef AE_SOCKETS_SUPPORT
/*php_sock = fd_to_php_sock((php_socket_t) fd);
ZEND_REGISTER_RESOURCE(cb->data, php_sock, php_sockets_le_socket());*/
static php_socket* 
fd_to_php_sock(php_socket_t fd) {
    php_socket*   php_sock;
    socklen_t     opt_length;

    /* Validate file descriptor */
    #ifndef PHP_WIN32
        if (fd >= 0 && fcntl(fd, F_GETFD) == -1) {
    #else
        if (fd == INVALID_SOCKET) {
    #endif
            php_error_docref(NULL TSRMLS_CC, E_WARNING, "invalid file descriptor");
            return NULL;
        }

        php_sock             = emalloc(sizeof *php_sock);
        php_sock->error      = 0;
        php_sock->zstream    = NULL;
        php_sock->type       = PF_UNSPEC;
        php_sock->bsd_socket = fd;

        opt_length = sizeof(php_sock->type);

        if (getsockopt(fd, SOL_SOCKET, SO_TYPE, &php_sock->type, &opt_length) != 0) {
            php_error_docref(NULL TSRMLS_CC, E_WARNING, "Unable to retrieve socket type");
            efree(php_sock);
            return NULL;
        }

    #ifndef PHP_WIN32
        php_sock->blocking = (fcntl(fd, F_GETFL) & O_NONBLOCK) == 0 ? 0 : 1;
    #else
        php_sock->blocking = 1;
    #endif
    return php_sock;
}
// #endif /* AE_SOCKETS_SUPPORT */

/* {{{ zval_to_fd
 * Get numeric file descriptor from PHP stream or Socket resource */
static php_socket_t 
zval_to_fd(zval **ppfd TSRMLS_DC) {
    php_socket_t  file_desc = -1;
    php_stream   *stream;
// #ifdef AE_SOCKETS_SUPPORT
    php_socket   *php_sock;
// #endif

    if (Z_TYPE_PP(ppfd) == IS_RESOURCE) {
        /* PHP stream or PHP socket resource  */
        if (ZEND_FETCH_RESOURCE_NO_RETURN(stream, php_stream *, ppfd, -1, NULL, php_file_le_stream())
                || ZEND_FETCH_RESOURCE_NO_RETURN(stream, php_stream *, ppfd, -1, NULL, php_file_le_pstream()))
        {
            php_stream_from_zval_no_verify(stream, ppfd);

            if (stream == NULL) {
                php_error_docref(NULL TSRMLS_CC, E_WARNING, "Failed obtaining fd");
                return -1;
            }

            /* PHP stream */
            if (php_stream_can_cast(stream, PHP_STREAM_AS_FD_FOR_SELECT | PHP_STREAM_CAST_INTERNAL) == SUCCESS) {
                if (php_stream_cast(stream, PHP_STREAM_AS_FD_FOR_SELECT,
                            (void*) &file_desc, 1) != SUCCESS || file_desc < 0) {
                    return -1;
                }
            } else if (php_stream_can_cast(stream, PHP_STREAM_AS_FD | PHP_STREAM_CAST_INTERNAL) == SUCCESS) {
                if (php_stream_cast(stream, PHP_STREAM_AS_FD,
                            (void*) &file_desc, 1) != SUCCESS || file_desc < 0) {
                    return -1;
                }
            } else if (php_stream_can_cast(stream, PHP_STREAM_AS_STDIO | PHP_STREAM_CAST_INTERNAL) == SUCCESS) {
                if (php_stream_cast(stream, PHP_STREAM_AS_STDIO | PHP_STREAM_CAST_INTERNAL,
                            (void*) &file_desc, 1) != SUCCESS || file_desc < 0) {
                    return -1;
                }
            } else { /* STDIN, STDOUT, STDERR etc. */
                file_desc = Z_LVAL_P(*ppfd);
            }
        } else {
            /* PHP socket resource */
// #ifdef AE_SOCKETS_SUPPORT
            if (ZEND_FETCH_RESOURCE_NO_RETURN(php_sock, php_socket *,ppfd, -1, NULL, php_sockets_le_socket())) {
                if (php_sock->error) {
                    if (!php_sock->blocking && php_sock->error == EINPROGRESS) {
#ifdef AE_DEBUG
                        php_error_docref(NULL TSRMLS_CC, E_NOTICE, "Operation in progress");
#endif
                    } else
                        return -1;
                }

                return php_sock->bsd_socket;
            } else {
                /* php_error_docref(NULL TSRMLS_CC, E_WARNING,
                        "either valid PHP stream or valid PHP socket resource expected");*/
            }
// #else
            /* php_error_docref(NULL TSRMLS_CC, E_WARNING,
                    "valid PHP stream resource expected"); */
// #endif
            return -1;
        }
    } else if (Z_TYPE_PP(ppfd) == IS_LONG) {
        /* Numeric fd */
        file_desc = Z_LVAL_PP(ppfd);
        if (file_desc < 0) {
            php_error_docref(NULL TSRMLS_CC, E_WARNING, "invalid file descriptor passed");
            return -1;
        }
    } else {
        /* Invalid fd */
        php_error_docref(NULL TSRMLS_CC, E_WARNING, "invalid file descriptor passed");
        return -1;
    }

    /* Validate file descriptor */
#ifndef PHP_WIN32
    if (file_desc >= 0 && fcntl(file_desc, F_GETFD) == -1) {
#else
    if (file_desc == INVALID_SOCKET) {
#endif
        php_error_docref(NULL TSRMLS_CC, E_WARNING, "fcntl: invalid file descriptor passed");
        return -1;
    }

    return file_desc;
}
/* }}} */

static zend_always_inline void 
aeEventCallback(aeEventLoop *el, int fd, void *privdata, int mask) {
    /* php_socket* php_sock; */
    zval*  arg_mask;
    ae_event_cb_t* cb = (ae_event_cb_t *)privdata;
    MAKE_STD_ZVAL(arg_mask);
    ZVAL_LONG(arg_mask, mask);
    call_event_cb(cb, (zval *)cb->data, arg_mask);
    
    /* TODO 需要一个合适的时机 减引用计数 */
    /* TODO !!!!!! TEST zval_ptr_dtor(&cb->data); */
    // TODO del 时候efree(cb)
    // efree(cb);
}

static zend_always_inline int 
aeTimerCallback(aeEventLoop *eventLoop, long long id, void *clientData) {
    zval* timer_id;
    ae_event_cb_t* cb = (ae_event_cb_t *)clientData;
    
    MAKE_STD_ZVAL(timer_id);
    ZVAL_LONG(timer_id, id);

    call_event_cb(cb, timer_id, NULL);
    /* TODO 需要一个合适的时机 减引用计数 */
    /* TODO zval_ptr_dtor(&cb->data); */
    // TODO 周期性事件不能efree
    // TODO efree(cb);
    if ((int)cb->data == AE_NOMORE) {
        efree(cb);
    }
    if (cb->data == NULL) {
        return AE_NOMORE;
    } else {
        return (int)cb->data; /* tick millisecond */
    }
}

static zend_always_inline void 
aeTimerFinalizer(struct aeEventLoop *eventLoop, void *clientData) {
    // if (clientData) {
        efree((ae_event_cb_t *)clientData);
        // memset(clientData, 0, sizeof(ae_event_cb_t));
    // }
}

struct aeEventLoop *
newAeEventloop() {
    struct aeEventLoop * el;
    el = aeCreateEventLoop(AE_DEFAULT_SETSIZE);
    /* TODO hook for php */
    aeSetBeforeSleepProc(el, NULL);
    aeStop(el);
    return el;
}

void 
releaseAeEventloop(struct aeEventLoop * el) {
    if (el != NULL) {
        aeDeleteEventLoop(el);
        el = NULL;
    }
}

/** {{{ proto public bool \Ae\EventLoop::start()
*/
PHP_METHOD(ae_eventloop, start) {
    if (AE->el->stop == 1) {
        aeMain(AE->el);
        RETURN_TRUE;
    } else {
        RETURN_FALSE;
    }
}
/* }}} */

/** {{{ proto public bool \Ae\EventLoop::stop()
*/
PHP_METHOD(ae_eventloop, stop) {
    if (AE->el->stop == 0) {
        aeStop(AE->el);
        RETURN_TRUE;
    } else {
        RETURN_FALSE;
    }
}
/* }}} */

/** {{{ proto public bool \Ae\EventLoop::add(mixed $fd, int $mask, callable $cb)
*/
PHP_METHOD(ae_eventloop, add) {
    int fd                          = -1;
    int mask                        = AE_NONE;
    zval** ppzfd                    = NULL;
    ae_event_cb_t*          cb      = NULL;
    zend_fcall_info        fci      = empty_fcall_info;
    zend_fcall_info_cache  fcc      = empty_fcall_info_cache;
    
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "Zlf", &ppzfd, &mask, &fci, &fcc) == FAILURE) {
        return;
    }

    if (ppzfd) {
        fd = (int)zval_to_fd(ppzfd TSRMLS_CC);
        /* TODO !!! 适当的时候减引用计数 */
        Z_ADDREF_PP(ppzfd);
    } else {
        RETURN_FALSE;
    }

    cb = new_event_cb(&fci, &fcc);
    cb->data = *ppzfd;

    if (aeCreateFileEvent(AE->el, fd, mask, aeEventCallback, cb) == -1) {
        efree(cb);
        RETURN_FALSE;
    } else {
        RETURN_TRUE;
    }
}
/* }}} */

/** {{{ proto public bool \Ae\EventLoop::del(mixed $fdOrTimerId[, int $mask])
*/
PHP_METHOD(ae_eventloop, del) {
    int fd;
    int mask = -1; /* mask == -1; del timer event*/
    zval **ppzfd = NULL;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "Z|l", &ppzfd, &mask) == FAILURE) {
        return;
    }

    if (mask == -1) {
        /* TODO efree(cb) */
        if (Z_TYPE_PP(ppzfd) != IS_LONG) {
            RETURN_FALSE;
        }
        if (aeDeleteTimeEvent(AE->el, (long long)Z_LVAL_PP(ppzfd)) == -1) {
            RETURN_FALSE;
        } else {
            RETURN_TRUE;
        }
    } else {
        if (ppzfd) {
            fd = (int)zval_to_fd(ppzfd TSRMLS_CC);
        } else {
            RETURN_FALSE;
        }

        aeDeleteFileEvent(AE->el, (int)fd, mask);
        RETURN_TRUE;
    }
}
/* }}} */

/** {{{ proto public bool \Ae\EventLoop::after(int $interval, callable $cb)
*/
PHP_METHOD(ae_eventloop, after) {
    long long id;
    long long milliseconds;
    ae_event_cb_t*          cb      = NULL;
    zend_fcall_info        fci      = empty_fcall_info;
    zend_fcall_info_cache  fcc      = empty_fcall_info_cache;
    
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "lf", &milliseconds, &fci, &fcc) == FAILURE) {
        return;
    }

    cb = new_event_cb(&fci, &fcc);
    cb->data = NULL;
    
    id = aeCreateTimeEvent(AE->el, milliseconds, aeTimerCallback, cb, aeTimerFinalizer);
    if (id == -1) {
        efree(cb);
        RETURN_FALSE;
    } else {
        RETURN_LONG(id);
    }
}
/* }}} */

/** {{{ proto public bool \Ae\EventLoop::tick(int $interval, callable $cb)
*/
PHP_METHOD(ae_eventloop, tick) {
    long long id;
    long long milliseconds;
    ae_event_cb_t*          cb      = NULL;
    zend_fcall_info        fci      = empty_fcall_info;
    zend_fcall_info_cache  fcc      = empty_fcall_info_cache;
    
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "lf", &milliseconds, &fci, &fcc) == FAILURE) {
        return;
    }

    cb = new_event_cb(&fci, &fcc);
    cb->data = (void *)milliseconds;
    
    id = aeCreateTimeEvent(AE->el, milliseconds, aeTimerCallback, cb, aeTimerFinalizer);
    if (id == -1) {
        efree(cb);
        RETURN_FALSE;
    } else {
        RETURN_LONG(id);
    }
}
/* }}} */

/** {{{ ae_eventloop_methods
*/
static zend_function_entry ae_eventloop_methods[] = {
    PHP_ME(ae_eventloop, start,         NULL,   ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)
    PHP_ME(ae_eventloop, stop,          NULL,   ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)
    PHP_ME(ae_eventloop, add,           NULL,   ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)
    PHP_ME(ae_eventloop, del,           NULL,   ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)
    PHP_ME(ae_eventloop, after,         NULL,   ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)
    PHP_ME(ae_eventloop, tick,          NULL,   ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)
    PHP_FE_END
};
/* }}} */

/** {{{ AE_STARTUP_FUNCTION
*/
AE_STARTUP_FUNCTION(eventloop)
{
    zend_class_entry ce;

    AE_INIT_CLASS_ENTRY(ce, "Ae_EventLoop", "Ae\\EventLoop", ae_eventloop_methods);
    ae_eventloop_ce = zend_register_internal_class_ex(&ce, NULL, NULL TSRMLS_CC);
    ae_eventloop_ce->ce_flags |= ZEND_ACC_FINAL_CLASS;

    zend_declare_class_constant_long(ae_eventloop_ce, ZEND_STRL(AE_EVENTLOOP_PROPERTY_NAME_MASK_NONE), AE_NONE TSRMLS_CC);
    zend_declare_class_constant_long(ae_eventloop_ce, ZEND_STRL(AE_EVENTLOOP_PROPERTY_NAME_MASK_READABLE), AE_READABLE TSRMLS_CC);
    zend_declare_class_constant_long(ae_eventloop_ce, ZEND_STRL(AE_EVENTLOOP_PROPERTY_NAME_MASK_WRITABLE), AE_WRITABLE TSRMLS_CC);

    return SUCCESS;
}
/* }}} */
