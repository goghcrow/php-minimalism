#!/usr/bin/env bash

rm -rf /tmp/phpdoc-cache-*
phpdoc --target ../php-doc --directory ./ --cache-folder /tmp

