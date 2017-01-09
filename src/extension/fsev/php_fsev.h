#ifndef FSEVENT_H
#define FSEVENT_H 1

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"

#define phpext_fsev_ptr &fsev_module_entry
extern zend_module_entry fsev_module_entry;


#define PHP_FSEV_MODULE_NAME   "fsev"
#define PHP_FSEV_BUILD_DATE    __DATE__ " " __TIME__
#define PHP_FSEV_VERSION       "0.0.1"
#define PHP_FSEV_AUTHOR        "rianhunter, xiaofeng"

PHP_MINIT_FUNCTION(fsev);
PHP_MSHUTDOWN_FUNCTION(fsev);


/* declaration of functions to be exported */
PHP_FUNCTION(fsev_open);
PHP_FUNCTION(fsev_decode);


// Event types that you can ask to listen for
#define FSE_INVALID             -1
#define FSE_CREATE_FILE          0
#define FSE_DELETE               1
#define FSE_STAT_CHANGED         2
#define FSE_RENAME               3
#define FSE_CONTENT_MODIFIED     4
#define FSE_EXCHANGE             5
#define FSE_FINDER_INFO_CHANGED  6
#define FSE_CREATE_DIR           7
#define FSE_CHOWN                8
#define FSE_XATTR_MODIFIED       9
#define FSE_XATTR_REMOVED       10
#define FSE_DOCID_CREATED       11
#define FSE_DOCID_CHANGED       12

#define FSE_MAX_EVENTS          13
#define FSE_ALL_EVENTS         998

#define FSE_EVENTS_DROPPED     999

// Actions for each event type
#define FSE_IGNORE    0
#define FSE_REPORT    1
#define FSE_ASK       2    // Not implemented yet

// The types of each of the arguments for an event
// Each type is followed by the size and then the
// data.  FSE_ARG_VNODE is just a path string
#define FSE_ARG_VNODE    0x0001   // next arg is a vnode pointer
#define FSE_ARG_STRING   0x0002   // next arg is length followed by string ptr
#define FSE_ARG_PATH     0x0003   // next arg is a full path
#define FSE_ARG_INT32    0x0004   // next arg is a 32-bit int
#define FSE_ARG_INT64    0x0005   // next arg is a 64-bit int
#define FSE_ARG_RAW      0x0006   // next arg is a length followed by a void ptr
#define FSE_ARG_INO      0x0007   // next arg is the inode number (ino_t)
#define FSE_ARG_UID      0x0008   // next arg is the file's uid (uid_t)
#define FSE_ARG_DEV      0x0009   // next arg is the file's dev_t
#define FSE_ARG_MODE     0x000a   // next arg is the file's mode (as an int32, file type only)
#define FSE_ARG_GID      0x000b   // next arg is the file's gid (gid_t)
#define FSE_ARG_FINFO    0x000c   // kernel internal only
#define FSE_ARG_DONE     0xb33f   // no more arguments

#define FSE_MAX_ARGS     12


// ioctl's on /dev/fsevents
typedef struct fsevent_clone_args {
    int8_t  *event_list;
    int32_t  num_events;
    int32_t  event_queue_depth;
    int32_t *fd;
} fsevent_clone_args;

#define	FSEVENTS_CLONE	_IOW('s', 1, fsevent_clone_args)

#endif /* FSEVENT_H */