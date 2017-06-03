#!/usr/bin/env bash

rm -rf load.php build/phpdump.phar build/novadump.phar
make clean
make load.php
make phar
cp build/phpdump.phar ~/Documents/zan-dev-doc/