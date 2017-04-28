import operator
import gdb

def str_val(zend_string):
    return str(gdb.inferiors()[0].read_memory(zend_string["val"], zend_string["len"]))

def zobjdump():
    obj_count = {}

    zend_uintptr_t = gdb.lookup_type("zend_uintptr_t")
    EG = gdb.parse_and_eval("executor_globals")
    objects_store = EG["objects_store"]

    handle = 1

    while handle < objects_store["top"]:
        _zend_object_ptr = objects_store["object_buckets"][handle]

        IS_OBJ_VALID = not (_zend_object_ptr.cast(zend_uintptr_t) & (1<<0))
        # IS_OBJ_VALID = 1

        if IS_OBJ_VALID:

            IS_OBJ_DESTRUCTOR_CALLED = _zend_object_ptr.dereference()["gc"]["u"]["v"]["flags"] & (1<<3)
            # IS_OBJ_DESTRUCTOR_CALLED = 0

            if (not IS_OBJ_DESTRUCTOR_CALLED):
                ce_ptr = _zend_object_ptr.dereference()["ce"]
                zend_string_class_name = ce_ptr.dereference()["name"].dereference()
                class_name = str_val(zend_string_class_name)
                print class_name

        handle += 1

    return