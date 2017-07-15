#!/usr/bin/env bash

# = =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= #
# @author xiaofeng                                              #
# @desc php object instances dump                               #
# @usage sudo zobjdump [php_pid]                                #
# = =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= #
# gdb-online-doc                                                #
# https://sourceware.org/gdb/onlinedocs/gdb/Python-API.html     #
# (gdb) python help(gdb)                                        #
# some API: gdb.Value.address,                                  #
#           gdb.Value.deference(), gdb.Value.cast()             #
#                                                               #
# 注意gdb内var_dump,print_r,debug_zval_dump受到当前进程输出重定向影响 #
# python gdb.execute("call php_var_dump(executor_globals.objects_store.object_buckets[handle]->properties_table, 0)")
# python gdb.execute("call zend_print_zval_r(executor_globals.objects_store.object_buckets[handle]->properties_table, 0)")
# python gdb.execute("call php_debug_zval_dump(executor_globals.objects_store.object_buckets[handle]->properties_table, 1)")
# python gdb.execute("call zend_gc_collect_cycles()")           #
# = =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= #
# >> PHP5                                                       #
# https://wiki.php.net/internals/engine/objects                 #
#   zend_object_handle is actually just an integer – that is,   #
#   an object is uniquely identified by an integer              #
#                                                               #
# >>    ZEND_API void *zend_object_store_get_object(const zval *zobject TSRMLS_DC)
# >> 275{                                                       #
# >> 276   zend_object_handle handle = Z_OBJ_HANDLE_P(zobject); #
# >> 277                                                        #
# >> 278   return EG(objects_store).object_buckets[handle].bucket.obj.object;
# >> 279}                                                       #
# >> p *(((zend_object *)((executor_globals.objects_store.object_buckets[handle])->bucket->obj->object))->ce)
# >> p *((zend_closure *)((zend_object *)((executor_globals.objects_store.object_buckets[handle])->bucket->obj->object)))
# = =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= #
# >> PHP7                                                       #
#                                                               #
# >> x/1bs executor_globals.objects_store.object_buckets[1]->ce->name->val
# >> https://github.com/php/php-src/blob/master/Zend/zend_objects_API.c
# >> p (executor_globals.objects_store.object_buckets[4615].properties_table+0)->u1.v.type
#                                                               #
# >>  对象存储校验代码                                             #
# >> https://github.com/php/php-src/blob/master/Zend/zend_objects_API.h#L30
# >> #define OBJ_BUCKET_INVALID            (1<<0)               #
# >> #define IS_OBJ_VALID(o)               (!(((zend_uintptr_t)(o)) & OBJ_BUCKET_INVALID))
# >> #define GC_FLAGS(p)                   (p)->gc.u.v.flags    #
# >> #define IS_OBJ_DESTRUCTOR_CALLED  (1<<3)                   #
#                                                               #
# >> 一些宏                                                      #
# >> #define Z_OBJ(zval)					(zval).value.obj    #
# >> #define Z_OBJ_P(zval_p)				Z_OBJ(*(zval_p))    #
# >> https://github.com/php/php-src/blob/master/Zend/zend_compile.h#L306
# >> #define OBJ_PROP(obj, offset) \                            #
# >> 	((zval*)((char*)(obj) + offset))                        #
# >> https://github.com/php/php-src/blob/master/Zend/zend_object_handlers.c#L73
# >> ZEND_API void rebuild_object_properties(zend_object *zobj) #
# = =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= #


# sudo yum-test install -y yz-php7-debuginfo.x86_64
# sudo yum-test install -y gdb
# sudo yum-test install -y yz-php7-swoole-soa-debuginfo.x86_64


php5_script()
{
cat>$FILE<<EOF
import operator

def zobjdump():
    obj_count = {}

    zend_object_ptr_t = gdb.lookup_type("zend_object").pointer()
    zend_class_entry_ptr_t = gdb.lookup_type("zend_class_entry").pointer()

    EG = gdb.parse_and_eval("executor_globals")
    objects_store = EG["objects_store"]

    handle = 1
    while handle < objects_store["top"]:
        obj = objects_store["object_buckets"][handle]

        if obj["valid"] and (not obj["destructor_called"]):

            zend_object = obj["bucket"]["obj"]["object"].cast(zend_object_ptr_t).dereference()
            class_name = zend_object["ce"].cast(zend_class_entry_ptr_t).dereference()["name"].string()

            if class_name in obj_count:
                obj_count[class_name] += 1
            else:
                obj_count[class_name] = 1

        handle += 1

    print "\n"
    print "%-12s %s" % ("#instances", "#class")
    print "-------------------------------------------"
    for class_name in sorted(obj_count, key=obj_count.get, reverse=True):
        print "%-12d %s" % (obj_count[class_name], class_name)

    return

if __name__ == "__main__":
    # gdb.execute("call zend_gc_collect_cycles()")
    gdb.execute("info proc")
    zobjdump()
EOF
}


# =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-


# wget http://codesearch.qima-inc.com/raw/php-7.1.3/.gdbinit
# sudo yum-test install yz-php7-swoole-soa-debuginfo.x86_64
# sudo yum-test install yz-php7-devel.x86_64
# sudo debuginfo-install yz-php7-7.1.3-2.el6.x86_64

php7_script()
{
cat>$FILE<<EOF
import operator
import gdb

def str_val(zend_string):
    return str(gdb.inferiors()[0].read_memory(zend_string["val"], zend_string["len"]))

def print_ht(class_name, handle, ht):
    bucket_ptr_t = gdb.lookup_type("Bucket").pointer()
    i = 0
    while i < ht["nNumUsed"]:
        bucket = (ht["arData"] + i).cast(bucket_ptr_t).dereference()
        if bucket["val"]["u1"]["v"]["type"] > 0:
            print class_name + "->" + str_val(bucket["key"].dereference())
            # gdb.execute("call zend_print_zval_r(executor_globals.objects_store.object_buckets[" + str(handle) + "]->properties_table+" + str(i) + ", 0)")
            gdb.execute("call php_debug_zval_dump(executor_globals.objects_store.object_buckets[" + str(handle) + "]->properties_table+" + str(i) + ", 1)")
        i += 1
	return

def gc_color_name(color):
    map = { 0x0000: "black", 0x8000: "white", 0x4000: "grey", 0xc000: "purple" }
    if color in map:
        return map[color]
    return "unknown"

def zend_get_type_by_const(type):
    map = { 1: "null", 2: "false", 3: "true", 4: "integer", 5: "float", 6: "string", 7: "array", 8: "object", 9: "resource", 13: "boolean", 14: "callable", 18: "void", 19: "iterable" }
    if type in map:
        return map[type]
    return "unknown"

def gc_trace_ref(ref_ptr):
    zend_object_ptr_t = gdb.lookup_type("zend_object").pointer()
    zend_array_ptr_t = gdb.lookup_type("zend_array").pointer()
    zend_uintptr_t = gdb.lookup_type("zend_uintptr_t")

    gc = ref_ptr.dereference()["gc"]
    # p gc_globals.roots.next->ref->gc.u.v.gc_info
    gc_info = gc["u"]["v"]["gc_info"]
    # p gc_globals.roots.next->ref->gc.u.v.type
    zval_type = int(gc["u"]["v"]["type"])
    # p gc_globals.roots.next->ref->gc.refcount
    refcount = gc["refcount"]
    # GC_ADDRESS(v) ((v) & ~GC_COLOR)
    gc_addr = gc_info & ~0xc000
    # GC_INFO_GET_COLOR(v) (((zend_uintptr_t)(v)) & GC_COLOR)
    # GC_REF_GET_COLOR(ref) GC_INFO_GET_COLOR(GC_INFO(ref))
    color = gc_info.cast(zend_uintptr_t) & 0xc000 # gdb.Value
    color_name = gc_color_name(int(color))

    if zval_type == 8: # IS_OBJECT
        obj = ref_ptr.cast(zend_object_ptr_t).dereference()
        class_name = str_val(obj["ce"].dereference()["name"].dereference())
        handle = obj["handle"]
        print "[%s] rc=%d addr=%d %s object(%s)#%d " % (ref_ptr, refcount, gc_addr, color_name, class_name, handle)

    elif zval_type == 7: # IS_ARRAY
        arr = ref_ptr.cast(zend_array_ptr_t).dereference()
        print "[%s] rc=%d addr=%d %s array(%d) " % (ref_ptr, refcount, gc_addr, color_name, arr["nNumOfElements"])
    else:
        # TODO print detail info
        print "[%s] rc=%d addr=%d %s %s " % (ref_ptr, refcount, gc_addr, color_name, zend_get_type_by_const(zval_type))
    return

def gc_root_list():
    GC_G = gdb.parse_and_eval("gc_globals")
    roots = GC_G["roots"]
    # gc_root_buffer *current = GC_G(roots).next;
    current = roots["next"] # (struct _gc_root_buffer *)

    # while (current != &GC_G(roots))
    while current != roots.address:
        ref_ptr = current.dereference()["ref"]
        gc_trace_ref(ref_ptr)
        current = current.dereference()["next"]
    return

def include_files():
    # todo executor.globals->include
    return

def zobjdump():
    obj_count = {}

    zend_uintptr_t = gdb.lookup_type("zend_uintptr_t")
    EG = gdb.parse_and_eval("executor_globals")
    objects_store = EG["objects_store"]

    # Skip 0 so that handles are true
    handle = 1

    while handle < objects_store["top"]:
        _zend_object_ptr = objects_store["object_buckets"][handle]

        IS_OBJ_VALID = not (_zend_object_ptr.cast(zend_uintptr_t) & (1<<0))

        if IS_OBJ_VALID:

            IS_OBJ_DESTRUCTOR_CALLED = _zend_object_ptr.dereference()["gc"]["u"]["v"]["flags"] & (1<<3)

            if (not IS_OBJ_DESTRUCTOR_CALLED):
                ce_ptr = _zend_object_ptr.dereference()["ce"]
                zend_string_class_name = ce_ptr.dereference()["name"].dereference()
                class_name = str_val(zend_string_class_name)

                if class_name in obj_count:
                    obj_count[class_name] += 1
                else:
                    obj_count[class_name] = 1

                # if class_name == filter_class_name:
                #    print handle
                #    ht = ce_ptr.dereference()["properties_info"]
                #    print_ht(class_name, handle, ht)

        handle += 1

    print "\n"
    print "%-12s %s" % ("#instances", "#class")
    print "-------------------------------------------"
    for class_name in sorted(obj_count, key=obj_count.get, reverse=True):
        print "%-12d %s" % (obj_count[class_name], class_name)

    return

if __name__ == "__main__":
    gdb.execute("info proc")

    # print "\ngc root list: before gc_collect_cycles"
    # print "-------------------------------------------"
    # gc_root_list()

    # print "\ncall gc_collect_cycles"
    # print "-------------------------------------------"
    # gdb.execute("call zend_gc_collect_cycles()")

    # print "\ngc root list: after gc_collect_cycles"
    # print "-------------------------------------------"
    # gc_root_list()

    zobjdump()
EOF
}

env_check()
{
    command -v php >/dev/null 2>&1 || { echo >&2 "php required"; exit 1; }
    command -v python >/dev/null 2>&1 || { echo >&2 "python required"; exit 1; }
    command -v gdb >/dev/null 2>&1 || { echo >&2 "gdb required"; exit 1; }
    if [[ $EUID -ne 0 ]]; then
        echo >&2 "root required"
        exit 1
    fi
    if [[ $(rpm -qa|grep '.*php.*debuginfo.*'|wc -l) == 0 ]]; then
        echo >&2 "php debuginfo required"
        exit 1
    fi
}


# echo "Usage: \"sudo $0 php_pid\"";
env_check

PHP_BIN=`which php`
PHP_VER=$(${PHP_BIN} -r 'echo PHP_MAJOR_VERSION;')
FILE=$(dirname $0)/zobjdump.py

if [ ${PHP_VER} == 5 ]; then
    php5_script
else
    php7_script
fi

if [ $# -ge 1 ]; then
    eval "gdb --batch -nx $PHP_BIN $1 -ex \"source ${FILE}\" 2>/dev/null" #  | tail -n +6
else
    IFS=' ' read -ra ADDR <<< "$(pidof php)"
    for pid in "${ADDR[@]}"; do
        eval "gdb --batch -nx ${PHP_BIN} ${pid} -ex \"source ${FILE}\" 2>/dev/null"
        printf "\n\n"
    done
fi

rm -f ${FILE}
