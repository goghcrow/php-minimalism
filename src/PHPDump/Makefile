include config.mk
include sources.mk

.PHONY: clean load.php phar

load.php: $(SOURCES)
	$(PHP) make-loader.php $^ > $@

phar: phpdump.phar

phpdump.phar: load.php $(SOURCES)
	$(PHP) -d phar.readonly=0 make-phar.php $@ $^

novadump.phar: load.php $(SOURCES)
	$(PHP) -d phar.readonly=0 make-phar.php $@ $^

clean:
	-rm -f build/phpdump.phar build/novadump.phar load.php

check: load.php
	$(PHP) $(PHPUNIT) -c phpunit.xml