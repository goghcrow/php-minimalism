#ifndef AE_TCPSERVER_H
#define AE_TCPSERVER_H

#include "php_ae.h" /* for AE_STARTUP_FUNCTION */

extern zend_class_entry *ae_tcpserver_ce;

AE_STARTUP_FUNCTION(tcpserver);

#define TCP_BACKLOG		511
#define MAX_ACCEPTS_PER_CALL 1000

#define error_log(...) fprintf(stderr, __VA_ARGS__);
#endif /* AE_TCPSERVER */