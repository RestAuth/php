SRCDIR=RestAuth/
DOCDIR=doc/
HTMLDIR=${DOCDIR}/html

all: 

test:
	phpunit tests

test-standards:
	-phpcs --report-width=120 tests/* RestAuth/*

coverage:
	phpunit --coverage-html ./doc/coverage tests/

examples:
	php examples/test.php

install:
	pear install -f package.xml

doc:
	mkdir -p ${HTMLDIR}
	phpdoc -s --title "php-restauth documentation" -o HTML:Smarty:PHP -dn php-restauth -d ${SRCDIR},tutorials -t ${HTMLDIR}

clean:
	rm -rf ${DOCDIR}
