#!/usr/bin/env bash

PHP_DIR=/Users/chuxiaofeng/yz_env/php
PHP_BIN=$PHP_DIR/bin

# make clean
$PHP_BIN/phpize --clean
$PHP_BIN/phpize
./configure
make clean
make

rm -rf $PHP_DIR/lib/php/extensions/no-debug-non-zts-20131226/ae.so
# cp ae.la $PHP_DIR/lib/php/extensions/no-debug-non-zts-20131226/
cp modules/ae.so $PHP_DIR/lib/php/extensions/no-debug-non-zts-20131226/
echo "========================================================================"
$PHP_BIN/php -dextension=ae.so -m | grep ae