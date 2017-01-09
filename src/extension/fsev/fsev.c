/*
  如何读取osx私有api代码来自 https://github.com/rianhunter/gfslogger 
*/

// for open(2)
#include <fcntl.h>

// for ioctl(2)
#include <sys/ioctl.h>
#include <sys/sysctl.h>

#include "php_fsev.h"

static zend_function_entry fsev_functions[] = {
    PHP_FE(fsev_open, NULL)
    PHP_FE(fsev_decode, NULL)
    { NULL, NULL,NULL }
};

//module entry
zend_module_entry fsev_module_entry = {
     STANDARD_MODULE_HEADER,
    "fsev",
    fsev_functions,               /* Functions */
    NULL,                         // MINIT Start of module      PHP_MINIT(fsev)     PHP_MINIT_FUNCTION(fsev){return SUCCESS;}
    NULL,                         // MSHUTDOWN End of module    PHP_MSHUTDOWN(fsev) PHP_MSHUTDOWN_FUNCTION(fsev){return SUCCESS;}
    NULL,                         // RINIT Start of request
    NULL,                         // RSHUTDOWN End of request
    NULL,                         // MINFO phpinfo additions
    PHP_FSEV_VERSION,
    STANDARD_MODULE_PROPERTIES
};

#ifdef COMPILE_DL_FSEV
ZEND_GET_MODULE(fsev)
#endif



static char large_buf[0x2000];

// fd 与 FILE* 相互转换
// int fildes = int fileno(FILE *stream)
// FILE* fp = fdopen(int fildes, const char *mode); // man fdopen

// 打开 /dev/fsevents php_stream
PHP_FUNCTION(fsev_open)
{
  FILE *fp;
  php_stream *stream;
  int newfd;
  int fd;
  signed char event_list[FSE_MAX_EVENTS];
  fsevent_clone_args retrieve_ioctl;



  event_list[FSE_CREATE_FILE]         = FSE_REPORT;
  event_list[FSE_DELETE]              = FSE_REPORT;
  event_list[FSE_STAT_CHANGED]        = FSE_REPORT;
  event_list[FSE_RENAME]              = FSE_REPORT;
  event_list[FSE_CONTENT_MODIFIED]    = FSE_REPORT;
  event_list[FSE_EXCHANGE]            = FSE_REPORT;
  event_list[FSE_FINDER_INFO_CHANGED] = FSE_REPORT;
  event_list[FSE_CREATE_DIR]          = FSE_REPORT;
  event_list[FSE_CHOWN]               = FSE_REPORT;
  event_list[FSE_XATTR_MODIFIED]      = FSE_REPORT;
  event_list[FSE_XATTR_REMOVED]       = FSE_REPORT;
  event_list[FSE_DOCID_CREATED]       = FSE_REPORT;
  event_list[FSE_DOCID_CHANGED]       = FSE_REPORT;

  fd = open("/dev/fsevents", 0, 2);
  if (fd < 0) {
    php_error_docref(NULL TSRMLS_CC, E_WARNING,"Unable to open /dev/fsevents using mode readonly");
    RETURN_FALSE;
  }

  retrieve_ioctl.event_list = event_list;
  retrieve_ioctl.num_events = sizeof(event_list);
  retrieve_ioctl.event_queue_depth = 0x400;
  retrieve_ioctl.fd = &newfd;

  if (ioctl(fd, FSEVENTS_CLONE, &retrieve_ioctl) < 0) {
    php_error_docref(NULL TSRMLS_CC, E_WARNING,"Unable to ioctl FSEVENTS_CLONE");
    RETURN_FALSE;
  }

  close(fd);

  fp = fdopen(newfd, "r");

  stream = php_stream_fopen_from_file_rel(fp, "r");
  php_stream_to_zval(stream, return_value);
  return;
}


// activates self as fsevent listener and displays fsevents
// must be run as root!! (at least on Mac OS X 10.4)
// while ((n = read(newfd, large_buf, sizeof(large_buf))) > 0) {
//   process_event_data(large_buf, n);
// }

/* event structure in mem:

event type: 4 bytes
event pid: sizeof(pid_t) (4 on darwin) bytes
args:
  argtype: 2 bytes
  arglen: 2 bytes
  argdata: arglen bytes
lastarg:
  argtype: 2 bytes = 0xb33f

*/


// 获取改动文件列表
PHP_FUNCTION(fsev_decode)
{
  char *in_buf;
  int size;
  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s",&in_buf, &size) == FAILURE)
  {
      RETURN_NULL();
  }

  array_init(return_value);

  int pos = 0;
  u_int16_t argtype;
  u_int16_t arglen;

  do {
    pos += 4;
    pos += sizeof(pid_t);

    while(1) {
      memcpy(&argtype, in_buf + pos, sizeof(argtype));
      pos += sizeof(argtype);

      if (FSE_ARG_DONE == argtype) {
        break;
      }

      memcpy(&arglen, in_buf + pos, sizeof(arglen));
      pos += sizeof(arglen);

      if (FSE_ARG_STRING == argtype) {
        add_next_index_stringl(return_value, (in_buf + pos), (uint) arglen, 1);
      }
      pos += arglen;
    }

  } while (pos < size);
}