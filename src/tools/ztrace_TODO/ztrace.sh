#!/usr/bin/env bash

# @author xiaofeng
# @usage sudo ztrace php_pid


ztrace_php7()
{
cat>$FILE<<EOF
import operator
import gdb
import time
import os

def ztrace():
    so_path = os.path.abspath("$1")
    print so_path
    gdb.execute("set \$dl_handle = (void *)dlopen(\"" + so_path + "\", 1)")
    gdb.execute("print \$dl_handle")
    gdb.execute("set zend_compile_file = ztrace_compile_file")
    gdb.execute("set zend_execute_ex = ztrace_execute_ex")
    gdb.execute("set zend_execute_internal = ztrace_execute_internal")
    gdb.execute("set zend_throw_exception_hook = ztrace_throw_exception_hook")
    gdb.execute("c")

    gdb.execute("set \$t = (int)dlclose(\$dl_handle)")
    gdb.execute("print \$t")
    return

if __name__ == "__main__":
    gdb.execute("info proc")
    ztrace()
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
    # 太慢了!
    # if [[ $(rpm -qa|grep '.*php.*debuginfo.*'|wc -l) == 0 ]]; then
    #    echo >&2 "php debuginfo required"
    #    exit 1
    # fi
}


# echo "Usage: \"sudo $0 php_pid\"";
env_check

PHP_BIN=`which php`
PHP_VER=$(${PHP_BIN} -r 'echo PHP_MAJOR_VERSION;')
FILE=$(dirname $0)/zobjdump.py

if [ ${PHP_VER} == 5 ]; then
    echo >&2 "php7 support only"
    exit 1
fi

if [ $# -ge 2 ]; then
    ztrace_php7 "/tmp/ztrace.so"
    eval "gdb --batch -nx $PHP_BIN $1 -ex \"source ${FILE}\" 2>/dev/null" #  | tail -n +6
fi

rm -f ${FILE}
