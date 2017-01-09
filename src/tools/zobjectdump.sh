#!/usr/bin/env bash

# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
# @author xiaofeng                                              #
# @desc php object instances summary                            #
# @require php-symbol gdb python                                #
# @doc gdb-online-doc                                           #
#	https://sourceware.org/gdb/onlinedocs/gdb/Python-API.html   #
# @doc https://wiki.php.net/internals/engine/objects            #
#   zend_object_handle is actually just an integer – that is,   #
#   an object is uniquely identified by an integer              #
# @usage sudo zobjdump php_pid                                  #
# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #

#    ZEND_API void *zend_object_store_get_object(const zval *zobject TSRMLS_DC)
# 275{
# 276	zend_object_handle handle = Z_OBJ_HANDLE_P(zobject);
# 277
# 278	return EG(objects_store).object_buckets[handle].bucket.obj.object;
# 279}
# p *(((zend_object *)((executor_globals.objects_store.object_buckets[4189])->bucket->obj->object))->ce)
# p *((zend_closure *)((zend_object *)((executor_globals.objects_store.object_buckets[4189])->bucket->obj->object)))

cat>zobjdump.py<<EOF
import operator

def zobjdump():
	obj_count = {}

	zend_object_ptr = gdb.lookup_type("zend_object").pointer()
	zend_class_entry_ptr = gdb.lookup_type("zend_class_entry").pointer()

	eg = gdb.parse_and_eval("executor_globals")
	objs = eg["objects_store"]

	handle = 1
	while handle < objs["top"]:
		obj = objs["object_buckets"][handle]
		if obj["valid"] and (not obj["destructor_called"]):
			class_name = obj["bucket"]["obj"]["object"].cast(zend_object_ptr).dereference()["ce"].cast(zend_class_entry_ptr).dereference()["name"].string()
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
	zobjdump()
EOF



DIR=$(dirname $0)
FILE=$DIR/zobjdump.py
BIN_PHP=`which php`

if [ $# == 1  ]; then
	eval "gdb --batch -nx $BIN_PHP $1 -ex \"source $FILE\" 2>/dev/null | tail -n +6"
	eval "rm -f $FILE"
else
	echo "Usage: \"sudo $0 php_pid\"";
	exit
fi