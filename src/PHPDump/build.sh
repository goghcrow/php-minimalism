#!/usr/bin/env bash

# rm -rf load.php
# rm -rf novadump.phar
make clean
make load.php
make phar
cp novadump.phar ~/Documents/zan-dev-doc/