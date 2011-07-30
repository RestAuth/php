SRCDIR=src/
DOCDIR=doc/
HTMLDIR=${DOCDIR}/html

all: 

test:
	phpunit tests

coverage:
	phpunit --coverage-html ./doc/coverage tests/

examples:
	php examples/test.php

doc:
	mkdir -p ${HTMLDIR}
	phpdoc --title "php-restauth documentation" -o HTML:Smarty:HandS -dn php-restauth -d ${SRCDIR},tutorials -t ${HTMLDIR}

clean:
	rm -rf ${DOCDIR}
