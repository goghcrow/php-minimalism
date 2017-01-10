#include "php.h"
#include "php_ae.h"
#include "aeTcpServer.h"
#include "aeUtil.h"

#include "ae/config.h"
#include "ae/ae.h"
#include "ae/anet.h"

#include <stdio.h>
#include <stdlib.h>
#include <time.h>
#include <signal.h>
#include <sys/wait.h>
#include <errno.h>
#include <assert.h>
#include <ctype.h>
#include <stdarg.h>
#include <arpa/inet.h>
#include <sys/stat.h>
#include <fcntl.h>
#include <sys/time.h>
#include <sys/resource.h>
#include <sys/uio.h>
#include <sys/un.h>
#include <limits.h>
#include <float.h>
#include <math.h>
#include <sys/resource.h>
#include <sys/utsname.h>
#include <locale.h>
#include <sys/socket.h>

extern ae_t * AE;
extern char neterr[1024];

zend_class_entry *ae_tcpserver_ce;

static void acceptTcpHandler(aeEventLoop *el, int fd, void *privdata, int mask);

static void 
acceptTcpHandler(aeEventLoop *el, int fd, void *privdata, int mask) {
    int cport, cfd, max = MAX_ACCEPTS_PER_CALL;
    char cip[NET_IP_STR_LEN];
    UNUSED(el);
    UNUSED(mask);
    UNUSED(privdata);

    while(max--) {
        cfd = anetTcpAccept(neterr, fd, cip, sizeof(cip), &cport);
        if (cfd == -1) {
            if (errno != EWOULDBLOCK)
                fprintf(stderr, "Accepting client connection: %s", neterr);
            return;
        }
        serverLog(LL_VERBOSE,"Accepted %s:%d\n", cip, cport);
        
        // acceptCommonHandler(cfd,0,cip);
        anetNonBlock(NULL, cfd);
        anetEnableTcpNoDelay(NULL, cfd);
        // if (server.tcpkeepalive)
            anetKeepAlive(NULL, cfd, 0); // server.tcpkeepalive
        if (aeCreateFileEvent(el, cfd, AE_READABLE, readQueryFromClient, NULL) == AE_ERR)
        {
            close(cfd);
            return;
        }
    }
}

/** {{{ proto public bool \Ae\TcpServer::__construct(string $ip, int $port)
*/
PHP_METHOD(ae_coroutine, __construct) {
    char *ip;
    int ip_len;
    int port;
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "sl", &ip, &ip_len, &port) == FAILURE) {
        return;
    }


    fd = anetTcpServer(neterr, port, ip, TCP_BACKLOG);
    if (fd == -1) {

    }

    if (anetNonBlock(NULL, fd) == ANET_ERR) {
        serverLog(LL_WARNING, "anetNonBlock fail\n");
        exit(1);
    }

    if (aeCreateFileEvent(AE->el, fd, AE_READABLE, acceptTcpHandler, NULL) == AE_ERR) {
        serverLog(LL_WARNING, "create file event fail\n");
        exit(1);
    }

}
/* }}} */

/** {{{ ae_tcpserver_methods
*/
static zend_function_entry ae_tcpserver_methods[] = {
    PHP_ME(ae_tcpserver, __construct,   NULL,   ZEND_ACC_PUBLIC | ZEND_ACC_CTOR)
    PHP_ME(ae_tcpserver, __destruct,    NULL,   ZEND_ACC_PUBLIC | ZEND_ACC_DTOR)
    PHP_ME(ae_tcpserver, start,         NULL,   ZEND_ACC_PUBLIC)
    PHP_FE_END
};
/* }}} */

/** {{{ AE_STARTUP_FUNCTION
*/
AE_STARTUP_FUNCTION(tcpserver)
{
    zend_class_entry ce;

    AE_INIT_CLASS_ENTRY(ce, "Ae_TcpServer", "Ae\\TcpServer", ae_tcpserver_methods);
    ae_tcpserver_ce = zend_register_internal_class_ex(&ce, NULL, NULL TSRMLS_CC);
    ae_tcpserver_ce->ce_flags |= ZEND_ACC_FINAL_CLASS;

    return SUCCESS;
}
/* }}} */