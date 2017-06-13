include config.mk
include sources.mk

.PHONY: clean phar

load.php: $(SOURCES)
	$(PHP) make-loader.php $^ > $@

phar: fakeserver.phar

fakeserver.phar: load.php $(SOURCES)
	$(PHP) -d phar.readonly=0 make-phar.php $@ $^

clean:
	rm -f build/fakeserver.phar load.php

check: load.php
	$(PHP) $(PHPUNIT) -c phpunit.xml