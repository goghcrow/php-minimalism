#!/usr/bin/env bash

rm -rf load.php build/fakeserver.phar
make clean
make load.php
make phar
cp build/fakeserver.phar ~/Documents/zan-dev-doc/