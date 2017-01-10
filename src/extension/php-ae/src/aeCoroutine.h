#ifndef AE_CCOROUTINE_H
#define AE_CCOROUTINE_H

#include "php_ae.h" /* for AE_STARTUP_FUNCTION */

extern zend_class_entry *ae_coroutine_ce;

AE_STARTUP_FUNCTION(coroutine);

PHP_METHOD(ae_coroutine, __construct);
PHP_METHOD(ae_coroutine, __destruct);
PHP_METHOD(ae_coroutine, open);
PHP_METHOD(ae_coroutine, close);
PHP_METHOD(ae_coroutine, new);
PHP_METHOD(ae_coroutine, resume);
PHP_METHOD(ae_coroutine, status);
PHP_METHOD(ae_coroutine, running);
PHP_METHOD(ae_coroutine, yield);

#endif  /* AE_CCOROUTINE_H */