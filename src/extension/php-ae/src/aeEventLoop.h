#ifndef AE_EVENT_LOOP_H
#define AE_EVENT_LOOP_H

#include "php_ae.h" /* for AE_STARTUP_FUNCTION */

extern zend_class_entry *ae_eventloop_ce;

AE_STARTUP_FUNCTION(eventloop);

#define AE_DEBUG

#include "ext/sockets/php_sockets.h"
#define AE_SOCKETS_SUPPORT

#define AE_DEFAULT_SETSIZE 65536

#define AE_EVENTLOOP_PROPERTY_NAME_MASK_NONE "NONE"
#define AE_EVENTLOOP_PROPERTY_NAME_MASK_READABLE "READABLE"
#define AE_EVENTLOOP_PROPERTY_NAME_MASK_WRITABLE "WRITABLE"

PHP_METHOD(ae_eventloop, start);
PHP_METHOD(ae_eventloop, stop);
PHP_METHOD(ae_eventloop, add);
PHP_METHOD(ae_eventloop, del);
PHP_METHOD(ae_eventloop, after);
PHP_METHOD(ae_eventloop, tick);

struct aeEventLoop * newAeEventloop();
void releaseAeEventloop();

#endif  /* AE_EVENT_LOOP_H */