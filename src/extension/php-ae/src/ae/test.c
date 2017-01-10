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

#include "config.h"
#include "ae.h"      /* Event driven programming library */
#include "anet.h"    /* Networking the easy way */

/* Error codes */
#define C_OK                    0
#define C_ERR                   -1

/* Anti-warning macro... */
#define UNUSED(V) ((void) V)

#define NET_IP_STR_LEN 46

/* Log levels */
#define LL_DEBUG 0
#define LL_VERBOSE 1
#define LL_NOTICE 2
#define LL_WARNING 3
#define LL_RAW (1<<10) /* Modifier to log without timestamp */
#define CONFIG_DEFAULT_VERBOSITY LL_NOTICE

#define MAX_ACCEPTS_PER_CALL 1000

char neterr[1024];



void serverLog(int level, const char *fmt, ...) {
    va_list ap;

    UNUSED(level);
    /*char msg[1024];*/
    /*if ((level&0xff) < server.verbosity) return;*/

    va_start(ap, fmt);
    vprintf(fmt, ap);
    va_end(ap);
}

int tickTest(struct aeEventLoop *eventLoop, long long id, void *clientData) {
    UNUSED(eventLoop);
    UNUSED(clientData);

    printf("hello %lld\n", id);
    return 0;
}

void readQueryFromClient(aeEventLoop *el, int fd, void *privdata, int mask) {
    UNUSED(el);
    UNUSED(mask);

    int nread;
    int buffer_size = 1024 * 16;
    char buffer[buffer_size];
    nread = read(fd, buffer, buffer_size);
    if (nread == -1) {
        if (errno == EAGAIN) {
            return;
        } else {
            serverLog(LL_VERBOSE, "Reading from client: %s\n",strerror(errno));
            return;
        }
    } else if (nread == 0) {
        serverLog(LL_VERBOSE, "Client closed connection");
        return;
    }
    buffer[nread] = NULL;

    serverLog(LL_VERBOSE, "Reading from client: %s\n", buffer);
}

void acceptTcpHandler(aeEventLoop *el, int fd, void *privdata, int mask) {
    int cport, cfd, max = MAX_ACCEPTS_PER_CALL;
    char cip[NET_IP_STR_LEN];
    UNUSED(el);
    UNUSED(mask);
    UNUSED(privdata);

    while(max--) {
        cfd = anetTcpAccept(neterr, fd, cip, sizeof(cip), &cport);
        if (cfd == ANET_ERR) {
            if (errno != EWOULDBLOCK)
                serverLog(LL_WARNING,
                    "Accepting client connection: %s", neterr);
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

int main(int argc, char **argv) {
    int fd;
    int tcp_backlog = 511;
    int port = 9999;
    aeEventLoop* el;

    fd = anetTcpServer(neterr, port, "0.0.0.0", tcp_backlog);
    if (fd == ANET_ERR) {
        serverLog(LL_WARNING, "create tcp server fail\n");
        exit(1);
    }
    if (anetNonBlock(NULL, fd) == ANET_ERR) {
        serverLog(LL_WARNING, "anetNonBlock fail\n");
        exit(1);
    }

    serverLog(LL_NOTICE, "Hello\n");
    el = aeCreateEventLoop(1024);
    if (aeCreateTimeEvent(el, atoi(argv[1]), tickTest, NULL, NULL) == AE_ERR) {
        exit(1);
    }
    // if (aeCreateFileEvent(el, fd, AE_READABLE, acceptTcpHandler, NULL) == AE_ERR) {
    //     serverLog(LL_WARNING, "create file event fail\n");
    //     exit(1);
    // }
    aeSetBeforeSleepProc(el, NULL);
    aeMain(el);
    aeDeleteEventLoop(el);
    return 0;
}
